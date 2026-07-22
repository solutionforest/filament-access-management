<?php

use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

it('creates a super admin user with the super-admin role via options', function () {
    $this->artisan('make:super-admin-user', [
        '--name' => 'Super Admin',
        '--email' => 'super-admin@example.com',
        '--password' => 'password123',
    ])->assertExitCode(0);

    $user = User::query()->where('email', 'super-admin@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Super Admin');

    $roleModel = Utils::getRoleModel();
    $role = $roleModel::query()->where('name', 'super-admin')->first();

    expect($role)->not->toBeNull();
    expect($user->hasRole('super-admin'))->toBeTrue();
});

it('creates a super admin user interactively when no options are given', function () {
    $this->artisan('make:super-admin-user')
        ->expectsQuestion('Email address', 'interactive-admin@example.com')
        ->expectsQuestion('Name', 'Interactive Admin')
        ->expectsQuestion('Password', 'password123')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'interactive-admin@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('super-admin'))->toBeTrue();
});

it('reuses the existing super-admin role when creating a second super admin user', function () {
    $this->artisan('make:super-admin-user', [
        '--name' => 'First Admin',
        '--email' => 'first-admin@example.com',
        '--password' => 'password123',
    ])->assertExitCode(0);

    $this->artisan('make:super-admin-user', [
        '--name' => 'Second Admin',
        '--email' => 'second-admin@example.com',
        '--password' => 'password123',
    ])->assertExitCode(0);

    $roleModel = Utils::getRoleModel();

    expect($roleModel::query()->where('name', 'super-admin')->count())->toBe(1);
});

it('creates a filament menu row via options', function () {
    // NOTE: solution-forest/filament-tree's ModelTree::bootModelTree() treats
    // `empty($parent_id)` (true for 0) as "unset" and rewrites it to the
    // package's default parent key (-1). So parent=0 is not round-tripped;
    // use a non-zero parent id here to verify the option is actually persisted.
    $menuModel = Utils::getMenuModel();
    $parent = $menuModel::create(['title' => 'Parent Menu', 'parent_id' => -1]);

    $this->artisan('make:filament-menu', [
        '--title' => 'Dashboard',
        '--icon' => 'heroicon-o-home',
        '--activeIcon' => 'heroicon-s-home',
        '--uri' => '/dashboard',
        '--badge' => 'new',
        '--badgeColor' => 'success',
        '--parent' => $parent->id,
    ])->assertExitCode(0);

    $menu = $menuModel::query()->where('title', 'Dashboard')->first();

    expect($menu)->not->toBeNull();
    expect($menu->icon)->toBe('heroicon-o-home');
    expect($menu->uri)->toBe('/dashboard');
    expect((int) $menu->parent_id)->toBe($parent->id);
});

it('creates a filament menu row interactively when title and parent are missing', function () {
    $this->artisan('make:filament-menu')
        ->expectsQuestion('Title', 'Interactive Menu')
        ->expectsQuestion('Parent ID', 1)
        ->assertExitCode(0);

    $menuModel = Utils::getMenuModel();
    $menu = $menuModel::query()->where('title', 'Interactive Menu')->first();

    expect($menu)->not->toBeNull();
    expect((int) $menu->parent_id)->toBe(1);
});

it('runs the upgrade command and migrates legacy /admin menu uris', function () {
    $menuModel = Utils::getMenuModel();

    $legacyMenu = $menuModel::create([
        'title' => 'Legacy Users',
        'uri' => '/admin/users',
        'parent_id' => -1,
        'is_filament_panel' => false,
    ]);

    $unrelatedMenu = $menuModel::create([
        'title' => 'External Link',
        'uri' => 'https://example.com',
        'parent_id' => -1,
        'is_filament_panel' => false,
    ]);

    $this->artisan('filament-access-management:upgrade')
        ->assertExitCode(0);

    $legacyMenu->refresh();
    $unrelatedMenu->refresh();

    expect($legacyMenu->uri)->toBe('/users');
    expect((bool) $legacyMenu->is_filament_panel)->toBeTrue();

    // Unrelated menu (not matching the legacy /admin pattern) should be untouched.
    expect($unrelatedMenu->uri)->toBe('https://example.com');
    expect((bool) $unrelatedMenu->is_filament_panel)->toBeFalse();
});
