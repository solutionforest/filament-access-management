<?php

use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\FilamentAccessManagement;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

if (! function_exists('svc')) {
    function svc(): FilamentAccessManagement
    {
        return app(FilamentAccessManagement::class);
    }
}

// ---------------------------------------------------------------------------
// createAdminRole()
// ---------------------------------------------------------------------------

it('creates the super-admin role with the web guard', function () {
    $role = svc()->createAdminRole();

    expect($role->name)->toBe(Utils::getSuperAdminRoleName())
        ->and($role->name)->toBe('super-admin')
        ->and($role->guard_name)->toBe('web');
});

it('createAdminRole is idempotent (firstOrCreate)', function () {
    $first = svc()->createAdminRole();
    $second = svc()->createAdminRole();

    expect($second->getKey())->toBe($first->getKey());

    $count = Utils::getRoleModel()::query()
        ->where('name', Utils::getSuperAdminRoleName())
        ->count();

    expect($count)->toBe(1);
});

// ---------------------------------------------------------------------------
// createPermissions()
// ---------------------------------------------------------------------------

it('creates permission rows from config with http_path and guard', function () {
    $created = svc()->createPermissions();

    $configPermissions = config('filament-access-management.permissions');

    // One row per configured permission.
    expect($created)->toHaveCount(count($configPermissions));

    $total = Utils::getPermissionModel()::query()->count();
    expect($total)->toBe(count($configPermissions));

    // A sample row carries the correct http_path and guard.
    $sample = Utils::getPermissionModel()::query()->where('name', 'users.viewAny')->first();
    expect($sample)->not->toBeNull()
        ->and($sample->http_path)->toBe('/admin/users')
        ->and($sample->guard_name)->toBe('web');
});

// ---------------------------------------------------------------------------
// createAdminPermission()
// ---------------------------------------------------------------------------

it('creates only the super-admin permission subset', function () {
    $created = svc()->createAdminPermission();

    $expectedNames = config('filament-access-management.roles.super-admin.role_permissions');

    expect($created)->toHaveCount(count($expectedNames));

    $createdNames = Utils::getPermissionModel()::query()->pluck('name')->all();

    sort($createdNames);
    $expectedSorted = $expectedNames;
    sort($expectedSorted);

    expect($createdNames)->toBe($expectedSorted);

    // Every created permission name is one of the configured full permission keys (a subset).
    $allPermissionKeys = array_keys(config('filament-access-management.permissions'));
    foreach ($createdNames as $name) {
        expect($allPermissionKeys)->toContain($name);
    }

    // And it is a strict subset (fewer than all permissions).
    expect(count($createdNames))->toBeLessThan(count($allPermissionKeys));
});

// ---------------------------------------------------------------------------
// user() / guard()
// ---------------------------------------------------------------------------

it('returns the authenticated user and the configured guard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(FilamentAuthenticate::user())->not->toBeNull()
        ->and(FilamentAuthenticate::user()->getAuthIdentifier())->toBe($user->getAuthIdentifier());

    expect(Utils::getFilamentAuthGuard())->toBe('web');

    $guard = FilamentAuthenticate::guard();
    expect($guard)->toBeInstanceOf(Guard::class)
        ->and($guard->user()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});

// ---------------------------------------------------------------------------
// userPermissions() + caching + clearPermissionCache()
// ---------------------------------------------------------------------------

it('returns cached user permissions and clears the custom cache tag', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $permModel = Utils::getPermissionModel();
    $permA = $permModel::create(['name' => 'demo.a', 'guard_name' => 'web']);
    $permB = $permModel::create(['name' => 'demo.b', 'guard_name' => 'web']);

    $user->givePermissionTo($permA);

    $cacheKey = Utils::getUserPermissionCacheKey($user);
    $cacheTag = Utils::getUserPermissionCacheTag();

    expect(Cache::has($cacheKey))->toBeFalse();

    $permissions = FilamentAuthenticate::userPermissions($user);

    expect($permissions)->toBeInstanceOf(Collection::class)
        ->and($permissions->pluck('name')->all())->toContain('demo.a');

    // The result is now cached under the user key, and the key is tracked in the tag.
    expect(Cache::has($cacheKey))->toBeTrue();
    expect(Cache::get($cacheTag))->toBeArray()
        ->and(Cache::get($cacheTag))->toContain($cacheKey);

    // Grant another permission WITHOUT clearing: cached value should not change (proves caching).
    $user->givePermissionTo($permB);
    $cached = FilamentAuthenticate::userPermissions($user);
    expect($cached->pluck('name')->all())->not->toContain('demo.b');

    // Clear the cache: custom cache key + tag are emptied.
    FilamentAuthenticate::clearPermissionCache();

    expect(Cache::has($cacheKey))->toBeFalse();
    expect(Cache::get($cacheTag))->toBeNull();

    // Fresh computation now reflects both permissions.
    $fresh = FilamentAuthenticate::userPermissions($user);
    expect($fresh->pluck('name')->all())->toContain('demo.a')
        ->and($fresh->pluck('name')->all())->toContain('demo.b');
});

// ---------------------------------------------------------------------------
// shouldPassThrough()
// ---------------------------------------------------------------------------

it('passes through the panel root, login, error and assets paths (Request)', function () {
    $svc = svc();

    $req = fn (string $path) => $svc->shouldPassThrough(Request::create('/'.ltrim($path, '/'), 'GET'));

    expect($req('admin'))->toBeTrue()          // panel root
        ->and($req('admin/login'))->toBeTrue() // login
        ->and($req('admin/error'))->toBeTrue() // error
        ->and($req('admin/assets/app.js'))->toBeTrue(); // assets
});

it('passes through the panel root as a string and configured excepts', function () {
    $svc = svc();

    // The configured "/" except resolves to the panel root, matched on the first iteration.
    expect($svc->shouldPassThrough(''))->toBeTrue();
});

it('does not pass through a normal protected path', function () {
    $svc = svc();

    expect($svc->shouldPassThrough('admin/users'))->toBeFalse();
    expect($svc->shouldPassThrough(Request::create('/admin/users', 'GET')))->toBeFalse();
});

it('should pass through the logout path', function () {
    $svc = svc();

    // FIXED: shouldPassThrough() now normalises getLoginUrl()/getLogoutUrl() to their
    // path component, so the logout path matches like the login path does.
    expect($svc->shouldPassThrough(Request::create('/admin/logout', 'GET')))->toBeTrue();
});

// ---------------------------------------------------------------------------
// allRoutes()
// ---------------------------------------------------------------------------

it('returns an array of admin route patterns without throwing', function () {
    $routes = svc()->allRoutes();

    expect($routes)->toBeArray();

    // Values are strings (route patterns) when present.
    foreach ($routes as $route) {
        expect($route)->toBeString();
    }
});

// ---------------------------------------------------------------------------
// Navigation registration + getters
// ---------------------------------------------------------------------------

it('registers and returns custom navigation groups and items', function () {
    $svc = svc();

    // Clean state: nothing registered -> null.
    expect($svc->getCustomNavigation())->toBeNull();

    $svc->registerNavigationGroups(['Group A']);
    $svc->registerNavigationItems([
        NavigationItem::make('Foo')->url('/admin/foo'),
    ]);

    expect($svc->getCustomNavigationGroups())->toBe(['Group A'])
        ->and($svc->getCustomNavigationItems())->toHaveCount(1);

    $nav = $svc->getCustomNavigation();
    expect($nav)->toBeInstanceOf(NavigationBuilder::class);
});

it('accepts NavigationGroup instances and a navigation builder closure', function () {
    $svc = svc();

    $svc->registerNavigationGroups([NavigationGroup::make()->label('Group B')]);
    expect($svc->getCustomNavigationGroups())->toHaveCount(1);

    $svc->navigation(fn (NavigationBuilder $builder) => $builder);
    expect($svc->getCustomNavigation())->toBeInstanceOf(NavigationBuilder::class);
});

it('returns null navigation for a freshly built service with no registrations', function () {
    $fresh = new FilamentAccessManagement;

    expect($fresh->getCustomNavigationGroups())->toBe([])
        ->and($fresh->getCustomNavigationItems())->toBe([])
        ->and($fresh->getCustomNavigation())->toBeNull();
});
