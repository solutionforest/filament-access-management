<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function afterSave(): void
    {
        if (! $this->record instanceof Model || ! is_a($this->record, Utils::getRoleModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
