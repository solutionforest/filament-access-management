<?php

namespace SolutionForest\FilamentAccessManagement;

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

class FilamentAccessManagement
{
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
}
