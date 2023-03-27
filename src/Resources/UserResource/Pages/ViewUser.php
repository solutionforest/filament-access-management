<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use SolutionForest\FilamentAccessManagement\Resources\UserResource;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
