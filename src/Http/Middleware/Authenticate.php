<?php

namespace SolutionForest\FilamentAccessManagement\Http\Middleware;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards): void
    {
        if (FilamentAuthenticate::shouldPassThrough($request)) {
            return;
        }
        if (! FilamentAuthenticate::guard()->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        if (! Permission::checkPermission($request->route()->uri())) {

            Permission::error();
        }

        return;
    }

    protected function redirectTo($request): string
    {
        return route('filament.auth.login');
    }
}
