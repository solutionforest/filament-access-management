<?php

use Filament\Facades\Filament;
use Livewire\Livewire;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\CreatePermission;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\EditPermission;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\ListPermissions;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\ViewPermission;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\CreateRole;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\EditRole;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\ListRoles;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\ViewRole;
use SolutionForest\FilamentAccessManagement\Resources\UserResource;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\CreateUser;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\EditUser;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\ListUsers;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\ViewUser;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::findOrCreate('super-admin', 'web');
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // HARNESS FIX: In this Testbench environment Livewire's DataStore mechanism is
    // NOT bound as a shared/singleton instance (app(DataStore::class) returns a fresh
    // object with an empty WeakMap on every resolve). That breaks Livewire's internal
    // per-component "store", so Component::getErrorBag() can never persist its
    // MessageBag and returns null during render — blowing up every Livewire::test()
    // render with "ViewErrorBag::put(): Argument #2 must be MessageBag, null given".
    // Re-binding a single shared DataStore instance restores normal Livewire state.
    $this->app->instance(
        \Livewire\Mechanisms\DataStore::class,
        new \Livewire\Mechanisms\DataStore
    );
});

// ---------------------------------------------------------------------------
// Panel registration
// ---------------------------------------------------------------------------

it('registers the three resources on the admin panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(UserResource::class)
        ->toContain(RoleResource::class)
        ->toContain(PermissionResource::class);
});

// ---------------------------------------------------------------------------
// UserResource
// ---------------------------------------------------------------------------

it('renders the user list page with records', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('creates a user', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

it('edits a user', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->assertOk()
        ->assertFormSet([
            'name' => 'Original Name',
            'email' => $user->email,
        ])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('Updated Name');
});

it('renders the user view page', function () {
    $user = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $user->getKey()])
        ->assertOk();
});

// ---------------------------------------------------------------------------
// RoleResource
// ---------------------------------------------------------------------------

it('renders the role list page with records', function () {
    $roles = collect(['editor', 'manager', 'viewer'])
        ->map(fn ($name) => Role::findOrCreate($name, 'web'));

    Livewire::test(ListRoles::class)
        ->assertOk()
        ->assertCanSeeTableRecords($roles);
});

it('creates a role', function () {
    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'content-editor',
            'guard_name' => 'web',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(config('permission.table_names.roles'), [
        'name' => 'content-editor',
        'guard_name' => 'web',
    ]);
});

it('edits a role', function () {
    $role = Role::findOrCreate('to-edit', 'web');

    Livewire::test(EditRole::class, ['record' => $role->getKey()])
        ->assertOk()
        ->assertFormSet([
            'name' => 'to-edit',
            'guard_name' => 'web',
        ])
        ->fillForm(['name' => 'edited-role'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->refresh()->name)->toBe('edited-role');
});

it('renders the role view page', function () {
    $role = Role::findOrCreate('view-me', 'web');

    Livewire::test(ViewRole::class, ['record' => $role->getKey()])
        ->assertOk();
});

// ---------------------------------------------------------------------------
// PermissionResource
// ---------------------------------------------------------------------------

it('renders the permission list page with records', function () {
    $permissions = collect(['view-post', 'edit-post', 'delete-post'])
        ->map(fn ($name) => Permission::findOrCreate($name, 'web'));

    Livewire::test(ListPermissions::class)
        ->assertOk()
        ->assertCanSeeTableRecords($permissions);
});

it('creates a permission', function () {
    Livewire::test(CreatePermission::class)
        ->fillForm([
            'name' => 'manage-things',
            'guard_name' => 'web',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(config('permission.table_names.permissions'), [
        'name' => 'manage-things',
        'guard_name' => 'web',
    ]);
});

it('edits a permission', function () {
    $permission = Permission::findOrCreate('edit-perm', 'web');

    Livewire::test(EditPermission::class, ['record' => $permission->getKey()])
        ->assertOk()
        ->assertFormSet([
            'name' => 'edit-perm',
            'guard_name' => 'web',
        ])
        ->fillForm(['name' => 'edited-perm'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($permission->refresh()->name)->toBe('edited-perm');
});

it('renders the permission view page', function () {
    $permission = Permission::findOrCreate('view-perm', 'web');

    Livewire::test(ViewPermission::class, ['record' => $permission->getKey()])
        ->assertOk();
});
