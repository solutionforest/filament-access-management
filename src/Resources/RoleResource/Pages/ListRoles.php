<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
