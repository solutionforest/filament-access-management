<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Models\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------
// FilamentUserHelpers
// ---------------------------------------------------------------------

it('guardName returns the configured filament auth guard', function () {
    $user = User::factory()->create();

    expect($user->guardName())->toBe('web');
});

it('getTable falls back to the parent table name when no custom user table is configured', function () {
    $user = new User;

    expect($user->getTable())->toBe('users');
});

it('isSuperAdmin returns false for a fresh user without the super-admin role', function () {
    $user = User::factory()->create();

    expect($user->isSuperAdmin())->toBeFalse();
});

it('isSuperAdmin returns true once the super-admin role is assigned', function () {
    $user = User::factory()->create();

    Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    $user->assignRole('super-admin');

    expect($user->isSuperAdmin())->toBeTrue();
});

it('getCachedPermissions returns a collection containing granted permissions', function () {
    $user = User::factory()->create();

    Permission::create(['name' => 'menu.viewAny', 'guard_name' => 'web']);
    $user->givePermissionTo('menu.viewAny');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    FilamentAuthenticate::clearPermissionCache();

    $permissions = $user->getCachedPermissions();

    expect($permissions)->toBeInstanceOf(Collection::class);
    expect($permissions->pluck('name'))->toContain('menu.viewAny');
});

it('the filament_auth() helper resolves the underlying FilamentAccessManagement service', function () {
    // FIXED: src/helpers.php filament_auth() now resolves the bound
    // 'filament-access-management' singleton, so instance methods work.
    expect(filament_auth())
        ->toBeInstanceOf(\SolutionForest\FilamentAccessManagement\FilamentAccessManagement::class);

    // A real instance method call no longer throws "undefined method".
    expect(fn () => filament_auth()->clearPermissionCache())->not->toThrow(\Error::class);
});

// ---------------------------------------------------------------------
// Models\Menu
// ---------------------------------------------------------------------

it('uses the configured filament_menu table', function () {
    $menu = Menu::create([
        'title' => 'Dashboard',
        'uri' => 'dashboard',
        'parent_id' => -1,
        'order' => 0,
    ]);

    expect($menu->getTable())->toBe('filament_menu');
    expect($menu->exists)->toBeTrue();
});

it('getNavigationUrl returns null when the uri column is empty', function () {
    $menu = new Menu(['title' => 'Empty', 'uri' => null]);

    expect($menu->getNavigationUrl())->toBeNull();
});

it('getNavigationUrl returns the raw uri when it is not a filament panel link', function () {
    $menu = Menu::create([
        'title' => 'External',
        'uri' => 'dashboard',
        'is_filament_panel' => false,
        'parent_id' => -1,
        'order' => 0,
    ])->fresh();

    // Saving normalizes the uri to start with a leading slash.
    expect($menu->uri)->toBe('/dashboard');
    expect($menu->getNavigationUrl())->toBe('/dashboard');
});

it('getNavigationUrl builds a panel url when is_filament_panel is true', function () {
    $menu = Menu::create([
        'title' => 'Panel Link',
        'uri' => 'dashboard',
        'is_filament_panel' => true,
        'parent_id' => -1,
        'order' => 0,
    ])->fresh();

    $url = $menu->getNavigationUrl();

    expect($url)->not->toBeNull();
    expect($url)->toContain('admin/dashboard');
});

it('saving hook defaults the icon and normalizes the uri for a leaf menu', function () {
    $menu = Menu::create([
        'title' => 'Leaf Item',
        'uri' => 'foo',
        'parent_id' => -1,
        'order' => 0,
    ]);

    expect($menu->icon)->toBe(Utils::getFilamentDefaultIcon());
    expect($menu->uri)->toBe('/foo');
});

it('saving hook does not override an already provided icon', function () {
    $menu = Menu::create([
        'title' => 'Leaf Item With Icon',
        'icon' => 'heroicon-o-star',
        'uri' => 'foo',
        'parent_id' => -1,
        'order' => 0,
    ]);

    expect($menu->icon)->toBe('heroicon-o-star');
});

it('clears the navigation cache after saving a menu', function () {
    $cacheKey = config('filament-access-management.cache.navigation.key', 'filament_navigation');

    Cache::put($cacheKey, ['dummy'], now()->addMinutes(10));
    expect(Cache::has($cacheKey))->toBeTrue();

    Menu::create([
        'title' => 'Cache Buster',
        'uri' => 'foo',
        'parent_id' => -1,
        'order' => 0,
    ]);

    expect(Cache::has($cacheKey))->toBeFalse();
});

it('clears the navigation cache after deleting a menu', function () {
    $cacheKey = config('filament-access-management.cache.navigation.key', 'filament_navigation');

    $menu = Menu::create([
        'title' => 'To Be Deleted',
        'uri' => 'foo',
        'parent_id' => -1,
        'order' => 0,
    ]);

    Cache::put($cacheKey, ['dummy'], now()->addMinutes(10));
    expect(Cache::has($cacheKey))->toBeTrue();

    $menu->delete();

    expect(Cache::has($cacheKey))->toBeFalse();
});
