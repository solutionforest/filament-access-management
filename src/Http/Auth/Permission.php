<?php

namespace SolutionForest\FilamentAccessManagement\Http\Auth;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Request;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Pages\Error as ErrorPage;

class Permission
{
    /**
     * Check permission.
     *
     * @param  string|array|Arrayable  $permission
     * @return true|void
     */
    public static function check($permission)
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (is_array($permission) || $permission instanceof Arrayable) {
            collect($permission)->each(function ($permission) {
                static::check($permission);
            });

            return true;
        }

        if (Str::of($permission)->contains(['/'])) {
            return static::checkPermission($permission);
        }

        if (FilamentAuthenticate::user()->cannot($permission)) {
            static::error();
        }
    }

    /**
     * Check permission by paths.
     *
     * @param  string|array|Arrayable  $paths
     * @return bool|array<string,bool>
     */
    public static function checkPermission($paths)
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = FilamentAuthenticate::user();

        if (is_string($paths)) {
            if (self::checkPathPermission($paths, $user)) {
                return true;
            }
        } else {
            return collect($paths)
                ->flatten()
                ->mapWithKeys(fn ($path) => [$path => self::checkPathPermission($path, $user)])
                ->toArray();
        }

        return false;
    }

    /**
     * Roles allowed to access.
     *
     * @param  string|array|Arrayable  $roles
     * @return true|void
     */
    public static function allow($roles)
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (! FilamentAuthenticate::user()->inRoles($roles)) {
            static::error();
        }
    }

    /**
     * Roles denied to access.
     *
     * @param  string|array|Arrayable  $roles
     * @return true|void
     */
    public static function deny($roles)
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (FilamentAuthenticate::user()->inRoles($roles)) {
            static::error();
        }
    }

    /**
     * Send error response page.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public static function error()
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
     *
     * @return mixed
     */
    public static function isSuperAdmin()
    {
        if (! FilamentAuthenticate::guard()->check()) {
            return false;
        }
        $user = FilamentAuthenticate::user();

        return method_exists($user, 'isSuperAdmin')
                ? $user->isSuperAdmin()
                : ($user->has('roles') ? collect($user->roles)->pluck('name')->contains(Utils::getSuperAdminRoleName()) : false) ?? false;
    }

    private static function checkPathPermission(string $path, $user = null)
    {
        if (FilamentAuthenticate::shouldPassThrough($path)) {
            return true;
        }

        $permissions = FilamentAuthenticate::userPermissions($user)
            ->filter(function ($permission) use ($path) {
                if (empty($permission->http_path)) {
                    return false;
                }
                $pattern = trim($permission->http_path, '/');
                $current = trim(admin_base_path($path), '/');

                return Utils::matchRequestPath($pattern, $current);
            })
            ->values();

        if ($permissions->isNotEmpty()) {
            return true;
        }

        return false;
    }
}
