<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
