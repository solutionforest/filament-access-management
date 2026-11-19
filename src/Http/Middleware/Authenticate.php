<?php

namespace SolutionForest\FilamentAccessManagement\Http\Middleware;

use Filament\Facades\Filament;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;

class Authenticate extends Middleware
{
    /**
     * @param  Request  $request
     * @param  array<int, string|null>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        if (FilamentAuthenticate::shouldPassThrough($request)) {
            return;
        }
        if (! FilamentAuthenticate::guard()->check()) {
            $this->unauthenticated($request, $guards);
        }

        if (! Permission::checkPermission($request->path())) {
            Permission::error();
        }
    }

    /**
     * @param  Request  $request
     */
    protected function redirectTo($request): ?string
    {
        return Filament::getLoginUrl();
    }
}
