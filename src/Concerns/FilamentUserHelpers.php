<?php

namespace SolutionForest\FilamentAccessManagement\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\Traits\HasRoles;

trait FilamentUserHelpers
{
    use HasRoles;

    public function getTable()
    {
        return Utils::getUserTableName() ?? parent::getTable();
    }

    public function guardName()
    {
        return Utils::getFilamentAuthGuard();
    }

    public function isSuperAdmin(): bool
    {
        if (! Schema::hasTable(Utils::getRoleTableName())) {
            return false;
        }

        return $this?->hasRole(Utils::getSuperAdminRoleName()) ?? false;
    }

    public function getCachedPermissions(): Collection
    {
        return
        FilamentAuthenticate::userPermissions($this);
    }
}
