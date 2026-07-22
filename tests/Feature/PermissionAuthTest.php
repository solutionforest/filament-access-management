<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

/**
 * Helpers -------------------------------------------------------------------
 */
function makeSuperAdmin(): User
{
    $user = User::factory()->create();

    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user->assignRole('super-admin');

    FilamentAuthenticate::clearPermissionCache();

    return $user;
}

/**
 * isSuperAdmin() ------------------------------------------------------------
 */
it('isSuperAdmin() returns false for a guest (no authenticated user)', function () {
    expect(Permission::isSuperAdmin())->toBeFalse();
});

it('isSuperAdmin() returns true when the user has the configured super-admin role', function () {
    expect(config('filament-access-management.roles.super-admin.name'))->toBe('super-admin');

    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    expect(Permission::isSuperAdmin())->toBeTrue();
});

it('isSuperAdmin() returns false for a normal authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(Permission::isSuperAdmin())->toBeFalse();
});

/**
 * checkPermission() ---------------------------------------------------------
 */
it('checkPermission() returns true for a super admin (bypass)', function () {
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    // Even an unknown/never-granted path returns true because of the bypass.
    expect(Permission::checkPermission('/admin/anything-at-all'))->toBeTrue();
});

it('checkPermission() (string) is true for a granted http_path and false otherwise', function () {
    $user = User::factory()->create();

    $perm = SpatiePermission::create([
        'name' => 'users.access',
        'guard_name' => 'web',
        'http_path' => '/admin/users*',
    ]);
    $user->givePermissionTo($perm);

    FilamentAuthenticate::clearPermissionCache();
    $this->actingAs($user);

    expect(Permission::checkPermission('/admin/users'))->toBeTrue();
    expect(Permission::checkPermission('/admin/permissions'))->toBeFalse();
});

it('checkPermission() (array) returns a map<path,bool>', function () {
    $user = User::factory()->create();

    $perm = SpatiePermission::create([
        'name' => 'users.access',
        'guard_name' => 'web',
        'http_path' => '/admin/users*',
    ]);
    $user->givePermissionTo($perm);

    FilamentAuthenticate::clearPermissionCache();
    $this->actingAs($user);

    $result = Permission::checkPermission(['/admin/users', '/admin/permissions']);

    expect($result)->toBeArray()
        ->and($result)->toBe([
            '/admin/users' => true,
            '/admin/permissions' => false,
        ]);
});

/**
 * check() -------------------------------------------------------------------
 */
it('check() returns true for a super admin without touching the gate', function () {
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    expect(Permission::check('some.permission.name'))->toBeTrue();
});

it('check() with a granted permission NAME passes without throwing', function () {
    $user = User::factory()->create();

    $perm = SpatiePermission::create([
        'name' => 'users.viewAny',
        'guard_name' => 'web',
    ]);
    $user->givePermissionTo($perm);

    FilamentAuthenticate::clearPermissionCache();
    $this->actingAs($user);

    // No return value on the success path, but crucially it must not throw.
    expect(fn () => Permission::check('users.viewAny'))->not->toThrow(HttpResponseException::class);
});

it('check() with a missing permission NAME triggers error() and throws', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => Permission::check('users.viewAny'))
        ->toThrow(HttpResponseException::class);
});

it('check() with a path (contains "/") delegates to checkPermission()', function () {
    // Super admin path: delegation still returns true via the bypass.
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    expect(Permission::check('/admin/users'))->toBeTrue();
});

/**
 * allow() / deny() ----------------------------------------------------------
 */
it('allow() returns true early for a super admin', function () {
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    expect(Permission::allow(['editor']))->toBeTrue();
});

it('deny() returns true early for a super admin', function () {
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    expect(Permission::deny(['editor']))->toBeTrue();
});

/**
 * allow()/deny() perform a role check via $user->inRoles() (FilamentUserHelpers).
 */
it('allow() passes silently when a non-super-admin holds one of the roles', function () {
    $user = User::factory()->create();
    Role::findOrCreate('editor', 'web');
    $user->assignRole('editor');
    $this->actingAs($user);

    // In an allowed role -> no error() redirect thrown.
    expect(fn () => Permission::allow(['editor']))->not->toThrow(HttpResponseException::class);
});

it('allow() throws (error redirect) when a non-super-admin lacks the roles', function () {
    $user = User::factory()->create();
    Role::findOrCreate('editor', 'web');
    $this->actingAs($user);

    expect(fn () => Permission::allow(['editor']))
        ->toThrow(HttpResponseException::class);
});

it('deny() passes silently when a non-super-admin lacks the roles', function () {
    $user = User::factory()->create();
    Role::findOrCreate('editor', 'web');
    $this->actingAs($user);

    expect(fn () => Permission::deny(['editor']))->not->toThrow(HttpResponseException::class);
});

it('deny() throws (error redirect) when a non-super-admin holds one of the roles', function () {
    $user = User::factory()->create();
    Role::findOrCreate('editor', 'web');
    $user->assignRole('editor');
    $this->actingAs($user);

    expect(fn () => Permission::deny(['editor']))
        ->toThrow(HttpResponseException::class);
});

/**
 * error() -------------------------------------------------------------------
 */
it('error() throws an HttpResponseException for a non-ajax request', function () {
    expect(fn () => Permission::error())
        ->toThrow(HttpResponseException::class);
});
