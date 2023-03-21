<?php

namespace SolutionForest\FilamentAccessManagement;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class FilamentAccessManagement
{
    public static function createAdminRole()
    {
        return Utils::getRoleModel()::firstOrCreate(
            ['name' => Utils::getAdminRoleName()],
            ['guard_name' => Utils::getFilamentAuthGuard()]
        );
    }
}
