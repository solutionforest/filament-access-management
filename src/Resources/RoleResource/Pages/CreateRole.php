<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\RoleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
