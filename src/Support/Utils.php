<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use BackedEnum;
use DateInterval;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Auth\User as FoundationUser;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Models\Menu;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Utils
{
    public static function getFilamentAuthGuard(): string
    {
        $guard = config('filament.auth.guard');

        if (is_string($guard) && $guard !== '') {
            return $guard;
        }

        $panelGuard = filament()->getCurrentOrDefaultPanel()?->getAuthGuard();

        return is_string($panelGuard) && $panelGuard !== '' ? $panelGuard : 'web';
    }

    public static function getSuperAdminRoleName(): string
    {
        $name = config('filament-access-management.roles.super-admin.name');

        return is_string($name) ? $name : 'super-admin';
    }

    /**
     * @return array<string, string>
     */
    public static function getPermissions(): array
    {
        $permissions = config('filament-access-management.permissions', []);

        if (! is_array($permissions)) {
            return [];
        }

        $result = [];

        foreach ($permissions as $name => $path) {
            if (is_string($name) && is_string($path)) {
                $result[$name] = $path;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public static function getSuperAdminPermissions(): array
    {
        $permissionNames = config('filament-access-management.roles.super-admin.role_permissions', []);
        $permissionNames = is_array($permissionNames) ? $permissionNames : [];

        return array_filter(
            self::getPermissions(),
            fn (string $name): bool => in_array($name, $permissionNames, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return class-string<FoundationUser>
     */
    public static function getUserModel(): string
    {
        $model = config('filament-access-management.auth.model', 'App\\Models\\User');

        return is_string($model) && is_a($model, FoundationUser::class, true)
            ? $model
            : FoundationUser::class;
    }

    /**
     * @return class-string<Role>
     */
    public static function getRoleModel(): string
    {
        $model = config('permission.models.role', Role::class);

        return is_string($model) && is_a($model, Role::class, true)
            ? $model
            : Role::class;
    }

    /**
     * @return class-string<Permission>
     */
    public static function getPermissionModel(): string
    {
        $model = config('permission.models.permission', Permission::class);

        return is_string($model) && is_a($model, Permission::class, true)
            ? $model
            : Permission::class;
    }

    /**
     * @return class-string<Menu>
     */
    public static function getMenuModel(): string
    {
        $model = config('filament-access-management.filament.navigation.model', Menu::class);

        return is_string($model) && is_a($model, Menu::class, true)
            ? $model
            : Menu::class;
    }

    public static function getUserTableName(): ?string
    {
        return self::nullableStringConfig('auth.providers.users.table');
    }

    public static function getRoleTableName(): ?string
    {
        return self::nullableStringConfig('permission.table_names.roles');
    }

    public static function getPermissionTableName(): ?string
    {
        return self::nullableStringConfig('permission.table_names.permissions');
    }

    public static function getMenuTableName(): ?string
    {
        return self::nullableStringConfig('filament-access-management.filament.navigation.table_name');
    }

    public static function getFilamentDefaultIcon(): string
    {
        $icon = config('filament-access-management.filament.navigationIcon.default', 'heroicon-o-document-text');

        return is_string($icon) && $icon !== '' ? $icon : 'heroicon-o-document-text';
    }

    public static function getUserPermissionCacheTag(): string
    {
        $tag = config('filament-access-management.cache.user_permissions.tag', 'user_permissions');

        return is_string($tag) && $tag !== '' ? $tag : 'user_permissions';
    }

    public static function getUserPermissionCacheKey(?Authenticatable $user = null): string
    {
        $user ??= FilamentAuthenticate::user();

        $prefix = config('filament-access-management.cache.user_permissions.key_prefix', 'user_spatie.permission.cache');
        $prefix = is_string($prefix) ? $prefix : 'user_spatie.permission.cache';

        $identifier = $user?->getAuthIdentifier();

        return $prefix.'_'.(is_scalar($identifier) ? (string) $identifier : '');
    }

    public static function getUserPermissionCacheExpirationTime(): DateInterval|int
    {
        $expiration = config('filament-access-management.cache.user_permissions.expiration_time');

        return $expiration instanceof DateInterval || is_int($expiration)
            ? $expiration
            : DateInterval::createFromDateString('24 hours');
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

    /**
     * @return list<class-string<resource>>
     */
    public static function getResources(): array
    {
        $resources = config('filament-access-management.filament.resources', []);

        if (! is_array($resources)) {
            return [];
        }

        return array_values(array_filter(
            $resources,
            fn (mixed $resource): bool => is_string($resource) && is_subclass_of($resource, Resource::class)
        ));
    }

    /**
     * @return list<class-string<Page>>
     */
    public static function getPages(): array
    {
        $pages = config('filament-access-management.filament.pages', []);

        if (! is_array($pages)) {
            return [];
        }

        return array_values(array_filter(
            $pages,
            fn (mixed $page): bool => is_string($page) && is_subclass_of($page, Page::class)
        ));
    }

    /**
     * Resolve a configured navigation icon to a string.
     */
    public static function resolveNavigationIcon(string $key, string|BackedEnum|Htmlable|null $default = null): string
    {
        $icon = config('filament-access-management.filament.navigationIcon.'.$key);

        if (is_string($icon) && $icon !== '') {
            return $icon;
        }

        if ($default instanceof BackedEnum) {
            return (string) $default->value;
        }

        if ($default instanceof Htmlable) {
            return $default->toHtml();
        }

        return $default ?? '';
    }

    private static function nullableStringConfig(string $key): ?string
    {
        $value = config($key);

        return is_string($value) ? $value : null;
    }
}
