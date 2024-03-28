<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Models;

class Utils
{
    public static function getFilamentAuthGuard(): string
    {
        return (string) config('filament.auth.guard', filament()->getCurrentPanel()?->getAuthGuard() ?? 'web');
    }

    public static function getSuperAdminRoleName(): string
    {
        return (string) config('filament-access-management.roles.super-admin.name');
    }

    public static function getPermissions(): array
    {
        return config('filament-access-management.permissions', []);
    }

    public static function getSuperAdminPermissions(): array
    {
        $permissionNames = config('filament-access-management.roles.super-admin.role_permissions', []);

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

    public static function getMenuModel(): string
    {
        return config('filament-access-management.filament.navigation.model', Models\Menu::class);
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

    public static function getMenuTableName():?string
    {
        return config('filament-access-management.filament.navigation.table_name');
    }

    public static function getFilamentDefaultIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.default', 'heroicon-o-document-text');
    }

    public static function getUserPermissionCacheTag(): string
    {
        return config('filament-access-management.cache.user_permissions.tag', 'user_permissions');
    }

    public static function getUserPermissionCacheKey(\Illuminate\Contracts\Auth\Authenticatable|null $user = null): string
    {
        $user ??= FilamentAuthenticate::user();

        return config('filament-access-management.cache.user_permissions.key_prefix', 'user_spatie.permission.cache').'_'.$user->getAuthIdentifier();
    }

    public static function getUserPermissionCacheExpirationTime(): \DateInterval|int
    {
        return config('filament-access-management.cache.user_permissions.expiration_time') ?: \DateInterval::createFromDateString('24 hours');
    }

    /**
     * @example
     *      Utils::matchRequestPath(admin_base_path('members'), admin_base_path('members'))
     *      Utils::matchRequestPath(admin_base_path('members*'), admin_base_path('members'))
     *      Utils::matchRequestPath(admin_base_path('members/*'), admin_base_path('members'))
     *      Utils::matchRequestPath(admin_base_path('members/* /view'), admin_base_path('members'))
     */
    public static function matchRequestPath(string $pattern, string $path): bool
    {
        $pattern = trim($pattern, '/');
        $path = trim($path, '/');

        if (! Str::contains($pattern, '*')) {
            return $path === $pattern;
        }

        if (Str::endsWith($pattern, ['/*'])) { // handle 'View' policy method
            $pattern = (string) Str::of($pattern)->beforeLast('/');

            if (! Str::endsWith($path, ['/create'])) { // except 'Create' policy method
                $path = (string) Str::of($path)->beforeLast('/');
            }
        }

        return Str::is($pattern, $path);
    }

    public static function getResources(): array
    {
        return (array) config('filament-access-management.filament.resources', []);
    }

    public static function getPages(): array
    {
        return (array) config('filament-access-management.filament.pages', []);
    }
}
