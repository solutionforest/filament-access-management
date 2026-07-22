<?php

use Illuminate\Support\Facades\Schema;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

it('boots the panel and runs migrations', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable(Utils::getRoleTableName()))->toBeTrue();
    expect(Schema::hasTable(Utils::getPermissionTableName()))->toBeTrue();
    expect(Schema::hasTable(Utils::getMenuTableName()))->toBeTrue();
    expect(Schema::hasColumn(Utils::getMenuTableName(), 'is_filament_panel'))->toBeTrue();
});

it('has the admin panel registered with the plugin', function () {
    $panel = filament()->getPanel('admin');

    expect($panel)->not->toBeNull();
    expect($panel->getPath())->toBe('admin');
    expect($panel->hasPlugin('filament-access-management-plugin'))->toBeTrue();
});

it('creates a user via factory', function () {
    $user = User::factory()->create();

    expect($user->exists)->toBeTrue();
    expect(Schema::hasTable('users'))->toBeTrue();
});
