<?php

namespace SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;

use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    public function afterSave(): void
    {
        if (! is_a($this->record, Utils::getPermissionModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
