<?php

namespace SolutionForest\FilamentAccessManagement\Http\Auth;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Pages;
use SolutionForest\FilamentAccessManagement\Support\Request;
use SolutionForest\FilamentAccessManagement\Support\Utils;

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
     *
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
        // abort(403);
        //TODO
        if (Request::isAjaxRequest()) {
            abort(403);
        }

        // $view = view('filament-access-management::errors.403', [
        //     'message' => null,
        // ]);


        // return redirect()->route('filament.pages.error/{code}', [
        //     'code' => 403
        // ]);
        // TODO
        // $view = Pages\Error::make(403, trans('filament-access-management::filament-access-management.errors.deny'))->render();

        // dd($view);

        throw new HttpResponseException(
            response()
            ->redirectToRoute('filament.pages.error', [
                'code' => 403
            ])
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

    /**
     * TODO: Cache user permissions. (Clear cache after update)
     * @see \Spatie\Permission\PermissionRegistrar
     */
    private static function checkPathPermission(string $path, $user = null)
    {
        if (FilamentAuthenticate::shouldPassThrough($path)) {
            return true;
        }

        $permissions = FilamentAuthenticate::userPermissions($user)
            ->filter(fn ($permission) => ! empty($permission->http_path) && Utils::matchRequestPath($permission->http_path, admin_base_path($path)))
            ->values();

        if ($permissions->isNotEmpty()) {

            return true;
        }
        return false;
    }
}
