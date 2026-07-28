<?php

use Illuminate\Support\Facades\Schema;
use SolutionForest\FilamentAccessManagement\Models\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\LegacyUser;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

/**
 * Upgrade path: an app moving from Filament v3 (old library version) to Filament
 * v5 while keeping this library. Verifies the migration seam and backward compat.
 */

/*
 * 1. Backward compatibility of the DEPRECATED FilamentUser trait ------------
 *    Apps that never switched to FilamentUserHelpers must keep working on v5.
 */
it('the deprecated FilamentUser trait still resolves guard and table on v5', function () {
    $user = LegacyUser::create([
        'name' => 'Legacy Admin',
        'email' => 'legacy@example.com',
        'password' => 'password',
    ]);

    expect($user->guardName())->toBe('web');
    expect($user->getTable())->toBe('users');
});

it('the deprecated FilamentUser trait still evaluates isSuperAdmin on v5', function () {
    $user = LegacyUser::create([
        'name' => 'Legacy Admin',
        'email' => 'legacy2@example.com',
        'password' => 'password',
    ]);

    expect($user->isSuperAdmin())->toBeFalse();

    Role::findOrCreate('super-admin', 'web');
    $user->assignRole('super-admin');

    expect($user->fresh()->isSuperAdmin())->toBeTrue();
});

it('the deprecated FilamentUser trait exposes inRoles() (regression) and cached permissions', function () {
    $user = LegacyUser::create([
        'name' => 'Legacy Editor',
        'email' => 'legacy3@example.com',
        'password' => 'password',
    ]);

    Role::findOrCreate('editor', 'web');
    $user->assignRole('editor');

    expect($user->inRoles(['editor']))->toBeTrue();
    expect($user->inRoles(['ghost']))->toBeFalse();

    $perm = SpatiePermission::create(['name' => 'reports.view', 'guard_name' => 'web', 'http_path' => '/admin/reports']);
    $user->givePermissionTo($perm);
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    expect($user->getCachedPermissions()->pluck('name'))->toContain('reports.view');
});

/*
 * 2. The upgrade_menu_table migration seam ---------------------------------
 *    On a v3 install the menu table had no is_filament_panel column; the
 *    upgrade migration adds it with a default of false.
 */
it('adds the is_filament_panel column to a legacy (v3) menu table', function () {
    $table = Utils::getMenuTableName();

    expect(Schema::hasColumn($table, 'is_filament_panel'))->toBeTrue();

    // Simulate the pre-upgrade (v3) schema by dropping the column, then re-run
    // the upgrade migration exactly as an upgrading app would.
    Schema::table($table, fn ($t) => $t->dropColumn('is_filament_panel'));
    expect(Schema::hasColumn($table, 'is_filament_panel'))->toBeFalse();

    $migration = require __DIR__.'/../../database/migrations/upgrade_menu_table.php.stub';
    $migration->up();

    expect(Schema::hasColumn($table, 'is_filament_panel'))->toBeTrue();
});

/*
 * 3. The filament-access-management:upgrade command -------------------------
 *    Migrates legacy /admin-prefixed menu URIs to panel-relative URIs and
 *    flags them as filament-panel links; leaves external URIs untouched.
 */
it('migrates legacy /admin menu uris and flags them as filament panel links', function () {
    // Legacy v3 rows: absolute /admin-prefixed uris, is_filament_panel=false.
    $dashboard = Menu::query()->create(['title' => 'Dashboard', 'uri' => '/admin', 'is_filament_panel' => false, 'parent_id' => -1, 'order' => 1]);
    $users = Menu::query()->create(['title' => 'Users', 'uri' => '/admin/users', 'is_filament_panel' => false, 'parent_id' => -1, 'order' => 2]);
    $external = Menu::query()->create(['title' => 'Docs', 'uri' => 'https://example.com/docs', 'is_filament_panel' => false, 'parent_id' => -1, 'order' => 3]);

    $this->artisan('filament-access-management:upgrade')->assertExitCode(0);

    $usersFresh = $users->fresh();
    expect($usersFresh->uri)->toBe('/users');
    expect($usersFresh->is_filament_panel)->toBeTrue();

    // '/admin' -> '' normalised by the Menu saving hook back to '/'
    $dashboardFresh = $dashboard->fresh();
    expect($dashboardFresh->is_filament_panel)->toBeTrue();

    // External link untouched.
    $externalFresh = $external->fresh();
    expect($externalFresh->uri)->toBe('https://example.com/docs');
    expect($externalFresh->is_filament_panel)->toBeFalse();
});

it('is idempotent: running the upgrade twice keeps already-migrated uris stable', function () {
    $users = Menu::query()->create(['title' => 'Users', 'uri' => '/admin/users', 'is_filament_panel' => false, 'parent_id' => -1, 'order' => 1]);

    $this->artisan('filament-access-management:upgrade')->assertExitCode(0);
    $this->artisan('filament-access-management:upgrade')->assertExitCode(0);

    $fresh = $users->fresh();
    expect($fresh->uri)->toBe('/users');
    expect($fresh->is_filament_panel)->toBeTrue();
});
