<?php

namespace SolutionForest\FilamentAccessManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SolutionForest\FilamentAccessManagement\FilamentAccessManagement
 */
class FilamentAccessManagement extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-access-management';
    }
}
