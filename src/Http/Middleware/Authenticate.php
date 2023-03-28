<?php

namespace SolutionForest\FilamentAccessManagement\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;

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

        if (! Permission::checkPermission($request->path())) {
            Permission::error();
        }
    }

    protected function redirectTo($request): string
    {
        return route('filament.auth.login');
    }
}
