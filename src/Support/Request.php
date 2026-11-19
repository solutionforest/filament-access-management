<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use Illuminate\Http\Request as HttpRequest;

class Request
{
    public static function isAjaxRequest(?HttpRequest $request = null): bool
    {
        $request = $request ?: request();

        return $request->ajax() && ! $request->pjax();
    }
}
