<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;

class Utils
{
    public static function getFilamentAuthGuard(): string
    {
        return (string) config('filament.auth.guard');
    }

    public static function getSuperAdminRoleName(): string
    {
        return (string) config('filament-access-management.roles.admin.name');
    }

    public static function getPermissions(): array
    {
        return config('filament-access-management.permissions', []);
    }

    public static function getAdminPermissions(): array
    {
        $permissionNames = config('filament-access-management.roles.admin.role_permissions', []);
        return array_filter(
            self::getPermissions(),
            fn ($name) => in_array($name, $permissionNames),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function getUserModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }

    public static function getRoleModel(): string
    {
        return config('permission.models.role', 'Spatie\\Permission\\Models\\Role');
    }

    public static function getPermissionModel(): string
    {
        return config('permission.models.permission', 'Spatie\\Permission\\Models\\Permission');
    }

    public static function getUserTableName(): ?string
    {
        return config('auth.providers.users.table');
    }
    public static function getRoleTableName(): ?string
    {
        return config('permission.table_names.roles');
    }

    public static function getPermissionTableName(): ?string
    {
        return config('permission.table_names.permissions');
    }

    public static function getCacheTags(): array
    {
        return config('filament-access-management.cache.tags', []);
    }

    public static function getUserPermissionCacheKey(\Illuminate\Contracts\Auth\Authenticatable|null $user = null): string
    {
        $user ??= FilamentAuthenticate::user();

        return config('filament-access-management.cache.user_permissions.key_prefix', 'user_spatie.permission.cache'). $user->getAuthIdentifier();
    }

    public static function getUserPermissionCacheExpirationTime(): \DateInterval|int
    {
        return config('permission.cache.expiration_time') ?: \DateInterval::createFromDateString('24 hours');
    }

    /**
     * @example
     *      Utils::matchRequestPath('members', admin_base_path('members'))
     *      Utils::matchRequestPath('members', admin_base_path('members*'))
     *      Utils::matchRequestPath('members', admin_base_path('members/*'))
     *      Utils::matchRequestPath('members', admin_base_path('members/* /view'))
     */
    public static function matchRequestPath(string $pattern, string $path): bool
    {
        $pattern = trim($pattern, '/');
        $path = trim($path, '/');


        if (! Str::contains($pattern, '*')) {
            return $path === $pattern;
        }

        if (Str::endsWith($path, ['/*'])) {

            $pattern = (string)Str::of($pattern)->beforeLast('/');
            $path = (string)Str::of($path)->beforeLast('/');
        }

        return Str::is($path, $pattern);
    }
}
