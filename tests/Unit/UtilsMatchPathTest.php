<?php

use SolutionForest\FilamentAccessManagement\Pages;
use SolutionForest\FilamentAccessManagement\Resources;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

// ---------------------------------------------------------------------
// Utils::matchRequestPath()
// ---------------------------------------------------------------------

it('matches an exact path with no wildcard', function () {
    expect(Utils::matchRequestPath('admin/users', 'admin/users'))->toBeTrue();
});

it('does not match differing paths with no wildcard', function () {
    expect(Utils::matchRequestPath('admin/users', 'admin/roles'))->toBeFalse();
});

it('trims leading and trailing slashes from both pattern and path', function () {
    expect(Utils::matchRequestPath('/admin/users/', 'admin/users'))->toBeTrue();
    expect(Utils::matchRequestPath('admin/users', '/admin/users/'))->toBeTrue();
});

it('matches using a non-trailing wildcard via Str::is', function () {
    // 'admin/users*' is not a trailing "/*" pattern, so it is passed straight to Str::is().
    expect(Utils::matchRequestPath('admin/users*', 'admin/users'))->toBeTrue();
    expect(Utils::matchRequestPath('admin/users*', 'admin/users/5'))->toBeTrue();
    expect(Utils::matchRequestPath('admin/users*', 'admin/roles'))->toBeFalse();
});

it('matches a mid-pattern wildcard segment', function () {
    expect(Utils::matchRequestPath('admin/users/*/edit', 'admin/users/5/edit'))->toBeTrue();
    expect(Utils::matchRequestPath('admin/users/*/edit', 'admin/users/5/delete'))->toBeFalse();
});

it('matches anything for a bare wildcard', function () {
    expect(Utils::matchRequestPath('*', 'anything/goes/here'))->toBeTrue();
});

describe('trailing "/*" pattern (View policy handling)', function () {
    it('strips the trailing segment off both pattern and path, so a resource id matches', function () {
        // pattern becomes "admin/users", path "admin/users/5" becomes "admin/users" too.
        expect(Utils::matchRequestPath('admin/users/*', 'admin/users/5'))->toBeTrue();
    });

    it('does not match the plain listing path (viewAny is a distinct permission)', function () {
        // path "admin/users" has no further segment to strip, beforeLast('/') collapses it to "admin".
        expect(Utils::matchRequestPath('admin/users/*', 'admin/users'))->toBeFalse();
    });

    it('excludes the "/create" path from matching the View pattern', function () {
        // The exception in the source keeps "admin/users/create" intact instead of stripping it,
        // so it no longer equals the stripped pattern "admin/users".
        expect(Utils::matchRequestPath('admin/users/*', 'admin/users/create'))->toBeFalse();
    });

    it('only strips a single trailing segment, so a two-level-deep path does not match', function () {
        // beforeLast('/') only removes the final "/profile" segment, leaving "admin/users/5"
        // which no longer equals the stripped pattern "admin/users".
        expect(Utils::matchRequestPath('admin/users/*', 'admin/users/5/profile'))->toBeFalse();
    });
});

it('returns false when the path is empty but the pattern is not', function () {
    expect(Utils::matchRequestPath('admin/users', ''))->toBeFalse();
});

it('returns true when both pattern and path are empty', function () {
    expect(Utils::matchRequestPath('', ''))->toBeTrue();
});

it('matches real permission patterns from config against real request paths', function () {
    $permissions = Utils::getPermissions();

    expect(Utils::matchRequestPath($permissions['users.viewAny'], 'admin/users'))->toBeTrue();
    expect(Utils::matchRequestPath($permissions['users.view'], 'admin/users/5'))->toBeTrue();
    expect(Utils::matchRequestPath($permissions['users.view'], 'admin/users/create'))->toBeFalse();
    expect(Utils::matchRequestPath($permissions['users.create'], 'admin/users/create'))->toBeTrue();
    expect(Utils::matchRequestPath($permissions['users.update'], 'admin/users/5/edit'))->toBeTrue();
});

// ---------------------------------------------------------------------
// admin_base_path() / admin_url() helpers
// ---------------------------------------------------------------------

it('returns the panel prefix for an empty path', function () {
    expect(admin_base_path(''))->toBe('/admin');
    expect(admin_base_path())->toBe('/admin');
});

it('prepends the panel prefix to a bare path', function () {
    expect(admin_base_path('users'))->toBe('/admin/users');
    expect(admin_base_path('/users'))->toBe('/admin/users');
});

it('leaves a path that already starts with the panel prefix unchanged', function () {
    expect(admin_base_path('admin/users'))->toBe('admin/users');
    expect(admin_base_path('/admin/users'))->toBe('admin/users');
});

it('converts a full URL input into just its path, then re-prefixes it', function () {
    // admin_base_path() (unlike admin_url()) always reduces a full URL down to its
    // path component via parse_url() and re-applies the admin prefix.
    expect(admin_base_path('https://example.com/foo'))->toBe('/admin/foo');
});

it('accepts an explicit panel id and resolves the same "admin" prefix', function () {
    expect(admin_base_path('users', 'admin'))->toBe('/admin/users');
});

it('passes a valid absolute URL straight through in admin_url()', function () {
    expect(admin_url('https://example.com/foo'))->toBe('https://example.com/foo');
});

it('builds a full application URL for a relative path in admin_url()', function () {
    expect(admin_url('users'))->toBe(url('/admin/users'));
    expect(admin_url('users'))->toEndWith('/admin/users');
});

// ---------------------------------------------------------------------
// Simple config-backed getters on Utils
// ---------------------------------------------------------------------

it('reads the super admin role name from config', function () {
    expect(Utils::getSuperAdminRoleName())->toBe('super-admin');
});

it('reads the default filament navigation icon from config', function () {
    expect(Utils::getFilamentDefaultIcon())->toBe('heroicon-o-document-text');
});

it('builds the user permission cache key from the configured prefix and the user id', function () {
    $user = User::factory()->create();

    expect(Utils::getUserPermissionCacheKey($user))
        ->toBe('user_spatie.permission.cache_'.$user->getAuthIdentifier());
});

it('reads the user permission cache tag from config', function () {
    expect(Utils::getUserPermissionCacheTag())->toBe('user_permissions');
});

it('reads the configured filament resources', function () {
    expect(Utils::getResources())->toBe([
        Resources\UserResource::class,
        Resources\RoleResource::class,
        Resources\PermissionResource::class,
    ]);
});

it('reads the configured filament pages', function () {
    expect(Utils::getPages())->toBe([
        Pages\Menu::class,
    ]);
});

it('reads the configured permissions map', function () {
    $permissions = Utils::getPermissions();

    expect($permissions)->toBeArray()
        ->and($permissions)->toHaveKey('users.viewAny', '/admin/users')
        ->and($permissions)->toHaveKey('users.view', '/admin/users/*')
        ->and($permissions)->toHaveKey('users.create', '/admin/users/create');
});
