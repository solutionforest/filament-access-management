<?php

namespace SolutionForest\FilamentAccessManagement\Facades;

use Closure;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use SolutionForest\FilamentAccessManagement\Support;
/**
 * @method static Authenticatable user() Get user model.
 * @method static \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard guard()
 * @method static Collection userPermissions(Authenticatable|null $user = null) Check user cached permissions.
 * @method static void clearPermissionCache()
 * @method static Model createAdminRole()
 * @method static array createAdminPermission()
 * @method static array createPermissions()
 * @method static bool shouldPassThrough(string|Request $request) Determine if the requesting path that should pass through verification.
 * @method static array allRoutes()
 * @method static Support\Menu menu() Get filament navigation helper.
 * @method static array getUserNavigationGroups() Get user navigation groups.
 * @method static void navigation(Closure $builder) Custom the filament navigation.
 * @method static void registerNavigationGroups(string[]|NavigationGroup[] $groups)
 * @method static void registerNavigationItems(NavigationItem[] $items)
 * @method static array getCustomNavigationGroups()
 * @method static array getCustomNavigationItems()
 * @method static ?NavigationBuilder getCustomNavigation() Get the custom filament navigation.
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
