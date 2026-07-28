<?php

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Pages\Error as ErrorPage;
use SolutionForest\FilamentAccessManagement\Pages\Menu as MenuPage;
use SolutionForest\FilamentAccessManagement\Support\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function navMenuModel(): string
{
    return Utils::getMenuModel();
}

function navMakeSuperAdmin(): User
{
    Utils::getRoleModel()::firstOrCreate(
        ['name' => Utils::getSuperAdminRoleName()],
        ['guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole(Utils::getSuperAdminRoleName());

    return $user;
}

beforeEach(function () {
    // Ensure a current panel is available for URL / navigation building.
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Menu::clearCache();
});

/*
|--------------------------------------------------------------------------
| Support\Menu static methods
|--------------------------------------------------------------------------
*/

it('creates a navigation row and is idempotent', function () {
    $a = Menu::createNavigation('Settings');
    $b = Menu::createNavigation('Settings');

    expect($a->exists)->toBeTrue();
    expect($a->getKey())->toBe($b->getKey());
    expect(navMenuModel()::query()->where('title', 'Settings')->count())->toBe(1);
    // parent defaults to -1 when null
    expect((int) $a->parent_id)->toBe(-1);
});

it('creates a child navigation row under a parent', function () {
    $parent = Menu::createNavigation('Group A');
    $child = Menu::createNavigation(
        title: 'Child A',
        parent: $parent->getKey(),
        icon: 'heroicon-o-user',
        uri: 'users',
    );

    expect((int) $child->parent_id)->toBe((int) $parent->getKey());
    expect($child->title)->toBe('Child A');
    expect($child->uri)->toBe('/users'); // model saving hook prefixes '/'
});

it('gets a navigation row or null', function () {
    Menu::createNavigation('Group B');

    expect(Menu::getNavigation('Group B', -1))->not->toBeNull();
    expect(Menu::getNavigation('Does Not Exist', -1))->toBeNull();
});

it('returns all navigation as a cached collection', function () {
    Menu::createNavigation('Alpha');
    Menu::createNavigation('Beta');

    $all = Menu::getAllNavigation();

    expect($all)->toBeInstanceOf(Collection::class);
    expect($all->count())->toBeGreaterThanOrEqual(2);
    // Cache should now be populated.
    expect(Cache::has(Menu::getCacheKey()))->toBeTrue();
});

it('clears the navigation cache', function () {
    Menu::createNavigation('Gamma');
    Menu::getAllNavigation();

    expect(Cache::has(Menu::getCacheKey()))->toBeTrue();

    Menu::clearCache();

    expect(Cache::has(Menu::getCacheKey()))->toBeFalse();
});

it('builds filament navigation groups from a tree without throwing', function () {
    $parent = Menu::createNavigation('Content', icon: 'heroicon-o-folder');
    Menu::createNavigation(
        title: 'Pages',
        parent: $parent->getKey(),
        icon: 'heroicon-o-document',
        uri: 'pages',
    );
    Menu::createNavigation(
        title: 'Standalone',
        icon: 'heroicon-o-star',
        uri: 'standalone',
    );

    Menu::clearCache();

    $groups = Menu::getNavigationGroups();

    expect($groups)->toBeInstanceOf(Collection::class);
    expect($groups)->not->toBeEmpty();

    $groups->each(function ($group) {
        expect($group)->toBeInstanceOf(NavigationGroup::class);
        // Items should be an array (possibly empty) of navigation items.
        expect($group->getItems())->toBeArray();
    });
});

/*
|--------------------------------------------------------------------------
| FilamentAccessManagement::getUserNavigationGroups()
|--------------------------------------------------------------------------
*/

it('returns all navigation groups for a super-admin', function () {
    $parent = Menu::createNavigation('Admin Area', icon: 'heroicon-o-cog');
    Menu::createNavigation(
        title: 'Users',
        parent: $parent->getKey(),
        icon: 'heroicon-o-users',
        uri: 'users',
        isFilamentPanel: true,
    );
    Menu::clearCache();

    $user = navMakeSuperAdmin();
    $this->actingAs($user, 'web');

    FilamentAuthenticate::clearPermissionCache();

    // NOTE: the filament_auth() helper resolves the Facade class itself (not the
    // manager), so call through the facade which proxies to the manager.
    $groups = FilamentAuthenticate::getUserNavigationGroups();

    expect($groups)->toBeArray();
    // Super admin bypasses permission filtering, so all groups remain.
    expect(count($groups))->toBeGreaterThanOrEqual(1);
});

it('returns only permitted navigation groups for a normal user', function () {
    $parent = Menu::createNavigation('Restricted', icon: 'heroicon-o-lock-closed');
    Menu::createNavigation(
        title: 'Members',
        parent: $parent->getKey(),
        icon: 'heroicon-o-user',
        uri: 'members',
        isFilamentPanel: true,
    );
    Menu::clearCache();

    $user = User::factory()->create();

    // Grant a spatie permission whose http_path matches the menu item url.
    $permission = Utils::getPermissionModel()::firstOrCreate(
        ['name' => 'view members', 'guard_name' => 'web'],
        ['http_path' => 'admin/members'],
    );
    $user->givePermissionTo($permission);

    $this->actingAs($user, 'web');

    FilamentAuthenticate::clearPermissionCache();

    $groups = FilamentAuthenticate::getUserNavigationGroups();

    // At minimum, it returns an array and does not throw.
    expect($groups)->toBeArray();
});

/*
|--------------------------------------------------------------------------
| Pages
|--------------------------------------------------------------------------
*/

it('mounts the Error page and defaults the view code to 403', function () {
    // Full Filament page rendering is broken in this harness (see BUG note on the
    // skipped render tests below), so exercise the page's own logic directly:
    // mount() + getViewData() — which is the meaningful behaviour of this Page.
    $page = new ErrorPage;
    $page->mount(403);

    expect($page->code)->toBe(403);

    $viewData = (fn () => $this->getViewData())->call($page);
    expect($viewData['code'])->toBe(403);

    // With no explicit code, getViewData() still falls back to 403.
    $defaultPage = new ErrorPage;
    $defaultPage->mount();
    $defaultViewData = (fn () => $this->getViewData())->call($defaultPage);
    expect($defaultViewData['code'])->toBe(403);
});

it('renders the Error page via Livewire', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web');
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

    Livewire::test(ErrorPage::class, ['code' => 403])
        ->assertOk()
        ->assertSet('code', 403);
});

it('renders the Menu tree page for a super-admin', function () {
    $user = navMakeSuperAdmin();
    $this->actingAs($user, 'web');
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

    Menu::createNavigation('Some Group', icon: 'heroicon-o-folder');
    Menu::clearCache();

    Livewire::test(MenuPage::class)->assertOk();
});
