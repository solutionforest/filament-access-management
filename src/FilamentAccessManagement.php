<?php

namespace SolutionForest\FilamentAccessManagement;

use Closure;
use Exception;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Support\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class FilamentAccessManagement
{
    protected ?Closure $navigationBuilder = null;

    /** @var list<string|NavigationGroup> */
    protected array $customNavigationGroups = [];

    /** @var list<NavigationItem> */
    protected array $customNavigationItems = [];

    /**
     * Get user model.
     */
    public function user(): ?Authenticatable
    {
        return static::guard()->user();
    }

    public function guard(): Guard|StatefulGuard
    {
        return Auth::guard(Utils::getFilamentAuthGuard() ?: 'web');
    }

    /**
     * Check user cached permissions.
     *
     * @return Collection<array-key, mixed>
     */
    public function userPermissions(?Authenticatable $user = null): Collection
    {
        $user ??= static::user();

        if (! $user instanceof Authenticatable) {
            return collect();
        }

        $cacheKey = Utils::getUserPermissionCacheKey($user);

        $cached = Cache::remember(
            $cacheKey,
            Utils::getUserPermissionCacheExpirationTime(),
            fn (): array => $this->permissionAttributesFor($user)
        );

        // Stale cache may unserialize as __PHP_Incomplete_Class when Eloquent
        // models/collections were stored previously.
        if (! is_array($cached)) {
            Cache::forget($cacheKey);
            $cached = $this->permissionAttributesFor($user);
            Cache::put($cacheKey, $cached, Utils::getUserPermissionCacheExpirationTime());
        }

        return $this->hydratePermissions($cached);
    }

    public function clearPermissionCache(): void
    {
        // Spatie/Permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Custom cache
        if ($tags = Cache::get(Utils::getUserPermissionCacheTag())) {
            if (is_array($tags)) {
                collect($tags)->each(function (mixed $tag): void {
                    if (is_string($tag)) {
                        Cache::forget($tag);
                    }
                });
            }

            Cache::forget(Utils::getUserPermissionCacheTag());
        }
    }

    public function createAdminRole(): Role
    {
        return Utils::getRoleModel()::firstOrCreate(
            ['name' => Utils::getSuperAdminRoleName()],
            ['guard_name' => Utils::getFilamentAuthGuard()]
        );
    }

    /**
     * @return list<\Spatie\Permission\Models\Permission>
     */
    public function createAdminPermission(): array
    {
        return $this->createPermissionModels(Utils::getSuperAdminPermissions());
    }

    /**
     * @return list<\Spatie\Permission\Models\Permission>
     */
    public function createPermissions(): array
    {
        return $this->createPermissionModels(Utils::getPermissions());
    }

    /**
     * Determine if the requesting path that should pass through verification.
     */
    public function shouldPassThrough(string|Request $request): bool
    {
        $fiPanel = Filament::getCurrentOrDefaultPanel();

        if (! $fiPanel instanceof Panel) {
            return true;
        }

        $panelPath = $fiPanel->getPath();

        $configured = config('filament-access-management.auth.except', []);
        $configured = is_array($configured) ? $configured : [];

        $excepts = [
            $panelPath,
            $panelPath.'/error*',
            parse_url((string) $fiPanel->getLoginUrl(), PHP_URL_PATH),
            parse_url((string) $fiPanel->getLogoutUrl(), PHP_URL_PATH),
            $panelPath.'/assets*',
        ];

        foreach ($configured as $except) {
            if (is_string($except)) {
                $excepts[] = $panelPath.$except;
            }
        }

        $current = is_string($request) ? $request : $request->path();
        $current = (string) str($panelPath)->append('/')->append(ltrim($current, '/'));

        foreach ($excepts as $except) {
            if (! is_string($except)) {
                continue;
            }

            if (! is_string($request)) {
                $except = trim($except, '/');

                if ($request->is($except)) {
                    return true;
                }
            }

            if (Utils::matchRequestPath($except, $current)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    public function allRoutes(): array
    {
        $configuredPrefix = config('filament.path');
        $prefix = is_string($configuredPrefix) ? trim($configuredPrefix, '/') : '';

        $paths = [];

        foreach (app(Router::class)->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if ($prefix !== '' && $prefix !== '/' && ! Str::startsWith($uri, $prefix)) {
                continue;
            }

            if (! Str::contains($uri, '{')) {
                $routePath = Str::replaceFirst($prefix, '', $uri.'*');

                if ($routePath !== '*') {
                    $paths[] = $routePath;
                }
            }

            $wildcardPath = preg_replace('/{.*}+/', '*', $uri);

            if (is_string($wildcardPath)) {
                $paths[] = Str::replaceFirst($prefix, '', $wildcardPath);
            }
        }

        $except = [
            '/error*',
            '/login*',
        ];

        $paths = array_values(array_filter(
            $paths,
            fn (string $path): bool => $path !== '' && ! Str::of($path)->is($except)
        ));

        $paths = array_map(fn (string $path): string => admin_base_path($path), $paths);

        sort($paths);

        $result = [];

        foreach ($paths as $path) {
            $result[$path] = $path;
        }

        return $result;
    }

    /**
     * Get filament navigation helper.
     */
    public function menu(): Menu
    {
        return app(Menu::class);
    }

    /**
     * Get user navigation groups.
     *
     * @return list<NavigationGroup>
     */
    public function getUserNavigationGroups(): array
    {
        $groups = static::menu()->getNavigationGroups();

        if (! Permission::isSuperAdmin()) {
            $urls = [];

            foreach ($groups as $group) {
                foreach (self::navigationItems($group) as $navItem) {
                    $url = $navItem->getUrl();

                    if ($url !== null) {
                        $urls[] = $url;
                    }
                }
            }

            $urls = array_values(array_unique($urls));

            $checkResult = Permission::checkPermission($urls);

            if (! is_bool($checkResult)) {
                $allowed = array_keys(array_filter($checkResult));

                $filtered = [];

                foreach ($groups as $navGroupKey => $navGroup) {
                    $items = [];

                    foreach (self::navigationItems($navGroup) as $navItem) {
                        if (in_array($navItem->getUrl(), $allowed, true)) {
                            $items[] = $navItem;
                        }
                    }

                    if ($items === []) {
                        continue;
                    }

                    $navGroup->items($items);
                    $filtered[$navGroupKey] = $navGroup;
                }

                $groups = collect($filtered);
            }
        }

        // Ensure no icon for Navigation group
        $result = [];

        foreach ($groups as $navigationGroup) {
            $navigationGroup->icon(null);
            $result[] = $navigationGroup;
        }

        return $result;
    }

    /**
     * Custom the filament navigation.
     */
    public function navigation(Closure $builder): void
    {
        $this->navigationBuilder = $builder;
    }

    /**
     * @param  list<string|NavigationGroup>  $groups
     */
    public function registerNavigationGroups(array $groups): void
    {
        $this->customNavigationGroups = array_merge($groups);
    }

    /**
     * @param  list<NavigationItem>  $items
     */
    public function registerNavigationItems(array $items): void
    {
        $this->customNavigationItems = array_merge($items);
    }

    /**
     * @return list<string|NavigationGroup>
     */
    public function getCustomNavigationGroups(): array
    {
        return $this->customNavigationGroups;
    }

    /**
     * @return list<NavigationItem>
     */
    public function getCustomNavigationItems(): array
    {
        return $this->customNavigationItems;
    }

    /**
     * Get the custom filament navigation.
     */
    public function getCustomNavigation(): ?NavigationBuilder
    {
        [$groups, $items, $builder] = [$this->getCustomNavigationGroups(), $this->getCustomNavigationItems(), $this->navigationBuilder];

        if (empty($groups) && empty($items) && empty($builder)) {
            return null;
        }

        $result = app(NavigationBuilder::class);

        if ($builder) {
            try {
                $result = $builder(app(NavigationBuilder::class));

                if (! $result instanceof NavigationBuilder) {
                    throw new Exception('Navigation builder must return a '.NavigationBuilder::class.' instance.');
                }
            } catch (Throwable $e) {
                throw new Exception(message: 'Failed to create navigation builder', previous: $e);
            }
        }

        $result->groups(collect($groups)->map(
            fn (string|NavigationGroup $group): NavigationGroup => $group instanceof NavigationGroup
                ? $group
                : NavigationGroup::make()->label($group)
        )->all());

        $result->items($items);

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permissionAttributesFor(Authenticatable $user): array
    {
        $this->rememberUserPermissionCacheKey($user);

        if (! method_exists($user, 'getAllPermissions')) {
            return [];
        }

        $permissions = $user->getAllPermissions();

        if (! ($permissions instanceof Collection || is_array($permissions))) {
            return [];
        }

        return collect($permissions)
            ->map(function (mixed $permission): array {
                return method_exists($permission, 'getAttributes')
                    ? $permission->getAttributes()
                    : (array) $permission;
            })
            ->values()
            ->all();
    }

    private function rememberUserPermissionCacheKey(Authenticatable $user): void
    {
        $tags = Cache::get(Utils::getUserPermissionCacheTag());
        $tags = is_array($tags) ? $tags : [];

        $tags = collect($tags)
            ->merge([Utils::getUserPermissionCacheKey($user)])
            ->unique()
            ->all();

        Cache::forever(Utils::getUserPermissionCacheTag(), $tags);
    }

    /**
     * @param  list<array<string, mixed>>  $attributes
     * @return Collection<int, \Spatie\Permission\Models\Permission>
     */
    private function hydratePermissions(array $attributes): Collection
    {
        $permissionModel = Utils::getPermissionModel();

        return collect($attributes)->map(function (mixed $item) use ($permissionModel) {
            $data = is_array($item) ? $item : (array) $item;

            $permission = new $permissionModel;
            $permission->setRawAttributes($data, true);
            $permission->exists = true;

            return $permission;
        });
    }

    /**
     * @param  array<string, string>  $permissions
     * @return list<\Spatie\Permission\Models\Permission>
     */
    private function createPermissionModels(array $permissions): array
    {
        $result = [];

        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $name => $httpPath) {
            $permission = $permissionModel::query()->whereRaw('name = ?', [$name])->first();

            if (! $permission instanceof \Spatie\Permission\Models\Permission) {
                $permission = new $permissionModel;
                $permission->setAttribute('name', $name);
                $permission->setAttribute('http_path', $httpPath);
                $permission->setAttribute('guard_name', Utils::getFilamentAuthGuard());
                $permission->save();
            }

            $result[] = $permission;
        }

        return $result;
    }

    /**
     * @return array<int, NavigationItem>
     */
    private static function navigationItems(NavigationGroup $group): array
    {
        $items = $group->getItems();

        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        return array_values(array_filter(
            $items,
            fn (mixed $item): bool => $item instanceof NavigationItem
        ));
    }
}
