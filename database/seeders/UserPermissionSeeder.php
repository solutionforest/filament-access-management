<?php

namespace SolutionForest\FilamentAccessManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $now = now();

        // create user
        Utils::getUserModel()::truncate();
        $adminUser = Utils::getUserModel()::create([
            'name' => 'admin',
            'email' => 'admin@'.Str::of(config('app.name'))->slug().'.com',
            'email_verified_at' => $now,
            'password' => bcrypt('admin'),
            'created_at' => $now,
        ]);

        // create role
        Utils::getRoleModel()::truncate();
        $role = FilamentAuthenticate::createAdminRole();

        // create permission
        Utils::getPermissionModel()::truncate();
        FilamentAuthenticate::createPermissions();

        // assign role permission
        $role->givePermissionTo(array_keys(Utils::getSuperAdminPermissions()));

        // assign user role
        $adminUser->assignRole(Utils::getSuperAdminRoleName());

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
