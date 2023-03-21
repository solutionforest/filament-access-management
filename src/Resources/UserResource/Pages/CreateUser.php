<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use SolutionForest\FilamentAccessManagement\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
