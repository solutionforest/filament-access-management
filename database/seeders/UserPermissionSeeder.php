<?php

namespace SolutionForest\FilamentAccessManagement\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
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
        Schema::disableForeignKeyConstraints(); // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $now = now();

        $appName = config('app.name');

        // create user
        $userModel = Utils::getUserModel();
        $adminUser = $userModel::query()->whereRaw('name = ?', ['admin'])->first();

        if (! $adminUser instanceof Model) {
            $adminUser = new $userModel;
            $adminUser->setAttribute('name', 'admin');
            $adminUser->setAttribute('email', 'admin@'.Str::of(is_string($appName) ? $appName : 'app')->slug().'.com');
            $adminUser->setAttribute('email_verified_at', $now);
            $adminUser->setAttribute('password', bcrypt('admin'));
            $adminUser->setAttribute('created_at', $now);
            $adminUser->save();
        }

        // create role
        Utils::getRoleModel()::truncate();
        $role = FilamentAuthenticate::createAdminRole();

        // create permission
        Utils::getPermissionModel()::truncate();
        FilamentAuthenticate::createPermissions();

        // assign role permission
        $role->givePermissionTo(array_keys(Utils::getSuperAdminPermissions()));

        // assign user role
        if (method_exists($adminUser, 'assignRole')) {
            $adminUser->assignRole(Utils::getSuperAdminRoleName());
        }

        Schema::enableForeignKeyConstraints(); // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
