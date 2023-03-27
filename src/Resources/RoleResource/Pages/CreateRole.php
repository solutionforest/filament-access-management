<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function afterSave(): void
    {
        if (! is_a($this->record, Utils::getRoleModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
