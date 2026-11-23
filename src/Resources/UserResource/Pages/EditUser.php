<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\UserResource;
use SolutionForest\FilamentAccessManagement\Support\Utils;

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
        if (! $this->record instanceof Model || ! is_a($this->record, Utils::getUserModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
