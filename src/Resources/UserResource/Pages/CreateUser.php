<?php

namespace SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use SolutionForest\FilamentAccessManagement\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
