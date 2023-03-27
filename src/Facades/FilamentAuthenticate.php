<?php

namespace SolutionForest\FilamentAccessManagement\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Authenticatable user()
 * @method static \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard guard()
 * @method static Collection userPermissions(Authenticatable|null $user = null)
 * @method static void clearPermissionCache()
 * @method static Model createAdminRole()
 * @method static array createAdminPermission()
 * @method static array createPermissions()
 * @method static bool shouldPassThrough(string|Request $request)
 * @method static array allRoutes()
 *
 * @see \SolutionForest\FilamentAccessManagement\FilamentAccessManagement
 */
class FilamentAuthenticate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-access-management';
    }
}
