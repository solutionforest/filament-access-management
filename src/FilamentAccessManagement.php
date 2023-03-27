<?php

namespace SolutionForest\FilamentAccessManagement;

use Closure;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Support\Request as RequestHelper;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class FilamentAccessManagement
{
    /** @var \Illuminate\Contracts\Cache\Repository */
    protected static $cache;

    public function __construct()
    {
        $this->initializeCache();
    }

    public function initializeCache()
    {
        static::$cache = $this->getCacheStoreFromConfig();
    }

    protected function getCacheStoreFromConfig()
    {
        return Cache::store(config('filament-access-management-cache.store', 'array'));
    }

    /**
     * Get user model.
     */
    public static function user(): \Illuminate\Contracts\Auth\Authenticatable|null
    {
        return static::guard()->user();
    }

    public static function guard(): \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
    {
        return Auth::guard(Utils::getFilamentAuthGuard() ?: 'web');
    }

    /**
     * Check user cached permissions.
     */
    public static function userPermissions(\Illuminate\Contracts\Auth\Authenticatable|null $user = null): Collection
    {
        $user ??= static::user();

        $cacheTags = self::$cache->tags(Utils::getCacheTags());

        $cached = $cacheTags->get(Utils::getUserPermissionCacheKey($user));

        if ($cached !== null) {
            return $cached;
        }

        $cacheTags->put(
            Utils::getUserPermissionCacheKey($user),
            method_exists($user, 'getAllPermissions') ? collect($user->getAllPermissions()) : collect(),
            Utils::getUserPermissionCacheExpirationTime()
        );

        return $cacheTags->get(Utils::getUserPermissionCacheKey($user));

    }

    public static function clearPermissionCache(): void
    {
        // Spatie/Permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Custom cache
        self::$cache->tags(Utils::getCacheTags())->flush();
    }

    public static function createAdminRole(): Model
    {
        return Utils::getRoleModel()::firstOrCreate(
            ['name' => Utils::getSuperAdminRoleName()],
            ['guard_name' => Utils::getFilamentAuthGuard()]
        );
    }

    public static function createAdminPermission(): array
    {
        $permissions = Utils::getAdminPermissions();
        return array_map(function ($httpPath, $name) {
            return Utils::getPermissionModel()::firstOrCreate(
                ['name' => $name],
                ['http_path' => $httpPath, 'guard_name' => Utils::getFilamentAuthGuard()]
            );
        }, $permissions, array_keys($permissions));
    }

    public static function createPermissions(): array
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
    public static function shouldPassThrough(string|Request $request): bool
    {
        $excepts = array_unique(array_merge(
            (array) config('filament-access-management.auth.except', []),
            ['/', '/error*']
        ));

        $current = is_string($request)? $request : $request->path();

        foreach ($excepts as $except) {

            $except = admin_base_path($except);

            if (!is_string($request)) {
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
}
