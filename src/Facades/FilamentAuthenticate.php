<?php

namespace SolutionForest\FilamentAccessManagement\Facades;

use Closure;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use SolutionForest\FilamentAccessManagement\FilamentAccessManagement;
use SolutionForest\FilamentAccessManagement\Support;

/**
 * @method static Authenticatable|null user() Get user model.
 * @method static Guard|StatefulGuard guard()
 * @method static Collection<array-key, mixed> userPermissions(Authenticatable|null $user = null) Check user cached permissions.
 * @method static void clearPermissionCache()
 * @method static \Spatie\Permission\Models\Role createAdminRole()
 * @method static list<\Spatie\Permission\Models\Permission> createAdminPermission()
 * @method static list<\Spatie\Permission\Models\Permission> createPermissions()
 * @method static bool shouldPassThrough(string|Request $request) Determine if the requesting path that should pass through verification.
 * @method static array<string, string> allRoutes()
 * @method static Support\Menu menu() Get filament navigation helper.
 * @method static list<NavigationGroup> getUserNavigationGroups() Get user navigation groups.
 * @method static void navigation(Closure $builder) Custom the filament navigation.
 * @method static void registerNavigationGroups(list<string|NavigationGroup> $groups)
 * @method static void registerNavigationItems(list<NavigationItem> $items)
 * @method static list<string|NavigationGroup> getCustomNavigationGroups()
 * @method static list<NavigationItem> getCustomNavigationItems()
 * @method static ?NavigationBuilder getCustomNavigation() Get the custom filament navigation.
 *
 * @see FilamentAccessManagement
 */
class FilamentAuthenticate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-access-management';
    }
}
