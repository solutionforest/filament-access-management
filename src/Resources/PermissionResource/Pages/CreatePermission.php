<?php

namespace SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\PermissionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;
}
