<?php

namespace SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    public function afterSave(): void
    {
        if (! $this->record instanceof Model || ! is_a($this->record, Utils::getPermissionModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
