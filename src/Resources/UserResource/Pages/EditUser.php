<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
