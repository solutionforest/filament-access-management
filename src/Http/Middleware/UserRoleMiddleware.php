<?php

namespace SolutionForest\FilamentAccessManagement\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserRoleMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        return $response;
    }
}
