<?php

namespace SolutionForest\FilamentAccessManagement\Http\Auth;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Pages\Error as ErrorPage;
use SolutionForest\FilamentAccessManagement\Support\Request;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission
{
    /**
     * Check permission.
     *
     * @param  string|array<array-key, mixed>|Arrayable<array-key, mixed>  $permission
     */
    public static function check(string|array|Arrayable $permission): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (is_array($permission) || $permission instanceof Arrayable) {
            foreach (collect($permission) as $singlePermission) {
                if (is_string($singlePermission)) {
                    static::check($singlePermission);
                }
            }

            return true;
        }

        if (Str::of($permission)->contains(['/'])) {
            $result = static::checkPermission($permission);

            return is_bool($result) ? $result : false;
        }

        $user = FilamentAuthenticate::user();

        if (! $user instanceof Authorizable) {
            static::error();

            return false;
        }

        if ($user->cannot($permission)) {
            static::error();

            return false;
        }

        return true;
    }

    /**
     * Check permission by paths.
     *
     * @param  string|array<array-key, mixed>|Arrayable<array-key, mixed>  $paths
     * @return bool|array<string, bool>
     */
    public static function checkPermission(string|array|Arrayable $paths): bool|array
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = FilamentAuthenticate::user();

        if (is_string($paths)) {
            if (self::checkPathPermission($paths, $user)) {
                return true;
            }

            return false;
        }

        $result = [];

        foreach (collect($paths)->flatten() as $path) {
            if (is_string($path)) {
                $result[$path] = self::checkPathPermission($path, $user);
            }
        }

        return $result;
    }

    /**
     * Roles allowed to access.
     *
     * @param  string|array<array-key, mixed>|Arrayable<array-key, mixed>  $roles
     */
    public static function allow(string|array|Arrayable $roles): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = FilamentAuthenticate::user();

        if (! $user instanceof Authenticatable) {
            static::error();

            return false;
        }

        if (! method_exists($user, 'inRoles') || ! $user->inRoles($roles)) {
            static::error();

            return false;
        }

        return true;
    }

    /**
     * Roles denied to access.
     *
     * @param  string|array<array-key, mixed>|Arrayable<array-key, mixed>  $roles
     */
    public static function deny(string|array|Arrayable $roles): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = FilamentAuthenticate::user();

        if (! $user instanceof Authenticatable) {
            static::error();

            return false;
        }

        if (method_exists($user, 'inRoles') && $user->inRoles($roles)) {
            static::error();

            return false;
        }

        return true;
    }

    /**
     * Send error response page.
     *
     * @throws HttpResponseException
     */
    public static function error(): void
    {
        if (Request::isAjaxRequest()) {
            abort(403);
        }

        $url = ErrorPage::getUrl(['code' => 403]);

        throw new HttpResponseException(
            response()->redirectTo($url)
        );
    }

    /**
     * If current user is super admin.
     */
    public static function isSuperAdmin(): bool
    {
        if (! FilamentAuthenticate::guard()->check()) {
            return false;
        }

        $user = FilamentAuthenticate::user();

        if (! $user instanceof Authenticatable) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin')) {
            return $user->isSuperAdmin() === true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(Utils::getSuperAdminRoleName()) === true;
        }

        return false;
    }

    private static function checkPathPermission(string $path, ?Authenticatable $user = null): bool
    {
        if (FilamentAuthenticate::shouldPassThrough($path)) {
            return true;
        }

        $permissions = collect(FilamentAuthenticate::userPermissions($user))
            ->filter(function (mixed $permission) use ($path): bool {
                if (! $permission instanceof SpatiePermission) {
                    return false;
                }

                $httpPath = $permission->getAttribute('http_path');

                if (! is_string($httpPath) || $httpPath === '') {
                    return false;
                }

                $pattern = trim($httpPath, '/');
                $current = trim(admin_base_path($path), '/');

                return Utils::matchRequestPath($pattern, $current);
            })
            ->values();

        return $permissions->isNotEmpty();
    }
}
