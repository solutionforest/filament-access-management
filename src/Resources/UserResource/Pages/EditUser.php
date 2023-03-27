<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    public function afterSave(): void
    {
        if (! is_a($this->record, Utils::getUserModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
