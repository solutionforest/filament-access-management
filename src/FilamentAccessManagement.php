<?php

namespace SolutionForest\FilamentAccessManagement;

use Closure;
use Filament\Navigation\NavigationBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Support\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\PermissionRegistrar;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Arr;
use Filament\Navigation\NavigationItem;

class FilamentAccessManagement
{
    protected ?Closure $navigationBuilder = null;

    protected array $customNavigationGroups = [];

    protected array $customNavigationItems = [];

    /**
     * Get user model.
     */
    public function user(): \Illuminate\Contracts\Auth\Authenticatable|null
    {
        return static::guard()->user();
    }

    public function guard(): \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
    {
        return Auth::guard(Utils::getFilamentAuthGuard() ?: 'web');
    }

    /**
     * Check user cached permissions.
     */
    public function userPermissions(\Illuminate\Contracts\Auth\Authenticatable|null $user = null): Collection
    {
        $user ??= static::user();

        return Cache::remember(
            Utils::getUserPermissionCacheKey($user),
            Utils::getUserPermissionCacheExpirationTime(),
            function () use ($user) {
                $tags = Cache::get(Utils::getUserPermissionCacheTag());
                if (is_null($tags)) {
                    $tags = [];
                }
                $tags = array_unique(array_merge($tags, [Utils::getUserPermissionCacheKey($user)]));
                Cache::forever(Utils::getUserPermissionCacheTag(), $tags);

                return method_exists($user, 'getAllPermissions') ? collect($user->getAllPermissions()) : collect();
            }
        );
    }

    public function clearPermissionCache(): void
    {
        // Spatie/Permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Custom cache
        if ($tags = Cache::get(Utils::getUserPermissionCacheTag())) {
            if (is_array($tags)) {
                collect($tags)->each(fn ($tag) => Cache::forget($tag));
            }

            Cache::forget(Utils::getUserPermissionCacheTag());
        }
    }

    public function createAdminRole(): Model
    {
        return Utils::getRoleModel()::firstOrCreate(
            ['name' => Utils::getSuperAdminRoleName()],
            ['guard_name' => Utils::getFilamentAuthGuard()]
        );
    }

    public function createAdminPermission(): array
    {
        $permissions = Utils::getSuperAdminPermissions();

        return array_map(function ($httpPath, $name) {
            return Utils::getPermissionModel()::firstOrCreate(
                ['name' => $name],
                ['http_path' => $httpPath, 'guard_name' => Utils::getFilamentAuthGuard()]
            );
        }, $permissions, array_keys($permissions));
    }

    public function createPermissions(): array
    {
        $permissions = Utils::getPermissions();

        return array_map(function ($httpPath, $name) {
            return Utils::getPermissionModel()::firstOrCreate(
                ['name' => $name],
                ['http_path' => $httpPath, 'guard_name' => Utils::getFilamentAuthGuard()]
            );
        }, $permissions, array_keys($permissions));
    }

    /**
     * Determine if the requesting path that should pass through verification.
     */
    public function shouldPassThrough(string|Request $request): bool
    {
        $excepts = array_unique(
            array_merge([
                admin_base_path('/'),
                admin_base_path('/error*'),
                'filament/logout',
                'filament/assets*',
            ], array_map(
                fn ($except) => admin_base_path($except),
                (array) config('filament-access-management.auth.except', [])
            )
            )
        );

        $current = is_string($request) ? $request : $request->path();

        foreach ($excepts as $except) {
            if (! is_string($request)) {
                $except = trim($except, '/');
                if ($request->is($except)) {
                    return true;
                }
            }

            $current = admin_base_path($current);

            if (Utils::matchRequestPath($except, $current)) {
                return true;
            }
        }

        return false;
    }

    public function allRoutes(): array
    {
        $prefix = trim(config('filament.path'), '/');

        $container = collect();

        $routes = collect(app('router')->getRoutes())->map(function ($route) use ($prefix, $container) {
            if (! Str::startsWith($uri = $route->uri(), $prefix) && $prefix && $prefix !== '/') {
                return;
            }

            if (! Str::contains($uri, '{')) {
                $route = Str::replaceFirst($prefix, '', $uri.'*');

                if ($route !== '*') {
                    $container->push($route);
                }
            }

            return Str::replaceFirst($prefix, '', preg_replace('/{.*}+/', '*', $uri));
        });

        $except = [
            '/error*',
            '/login*',
        ];

        return $container
            ->merge($routes)
            ->filter(fn ($path) => filled($path) && ! Str::of($path)->is($except))
            ->map(fn ($path) => admin_base_path($path))
            ->sort()
            ->keyBy(fn ($path) => $path)
            ->all();
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
     */
    public function getUserNavigationGroups(): array
    {
        $groups = static::menu()->getNavigationGroups();

        if (! Permission::isSuperAdmin()) {
            $menu = $groups;

            $checkResult = Permission::checkPermission(
                collect($menu)
                    ->map(fn (NavigationGroup $item) => $item->getItems())
                    ->flatten()
                    ->map(fn (NavigationItem $navItem) => $navItem->getUrl())
                    ->filter()
                    ->unique()
                    ->toArray()
            );

            if (! is_bool($checkResult)) {
                $groups = collect();

                $checkResult = array_keys(array_filter($checkResult));
                foreach ($menu as $navGroupKey => $navGroup) {
                    if ($navGroup instanceof NavigationGroup) {

                        $newNavGroup = $navGroup;

                        $newNavGroup->items(
                            collect($navGroup->getItems())
                                ->filter(fn (NavigationItem $navItem) => in_array($navItem->getUrl(), $checkResult))
                                ->values()
                                ->toArray()
                        );

                        if (count($newNavGroup->getItems()) > 0) {
                            $groups->put($navGroupKey, $newNavGroup);
                        }
                    }
                }
            }
        }

        // Ensure no icon for Navigation group
        $groups->each(fn (NavigationGroup $navigationGroup) => $navigationGroup->icon(null));

        return $groups->toArray();
    }

    /**
     * Custom the filament navigation.
     */
    public function navigation(Closure $builder): void
    {
        $this->navigationBuilder = $builder;
    }

    /**
     * @param string[]|NavigationGroup[] $groups
     */
    public function registerNavigationGroups(array $groups): void
    {
        $this->customNavigationGroups = array_merge($groups);
    }

    /**
     * @param Navigation\NavigationItem[] $items
     */
    public function registerNavigationItems(array $items): void
    {
        $this->customNavigationItems = array_merge($items);
    }

    public function getCustomNavigationGroups(): array
    {
        return $this->customNavigationGroups;
    }

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
                /** @var NavigationBuilder */
                $result = $builder(app(NavigationBuilder::class));
            } catch (\Throwable $e) {
                throw new \Exception(message: 'Failed to create navigation builder', previous: $e);
            }
        }
        $result->groups(collect($groups)->map(fn (NavigationGroup|string $group) => $group instanceof NavigationGroup ? $group : NavigationGroup::make()->label($group))->toArray());
        $result->items($items);

        return $result;
    }
}
