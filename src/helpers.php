<?php

// namespace SolutionForest\FilamentAccessManagement;

use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;

if (! function_exists('filament_auth')) {
    function filament_auth(): FilamentAuthenticate
    {
        return app(FilamentAuthenticate::class);
    }
}

if (! function_exists('admin_url')) {
    /**
     * Get admin url.
     *
     * @param  string  $path
     * @param  mixed  $parameters
     * @param  bool  $secure
     * @return string
     */
    function admin_url($path = '', $parameters = [], $secure = null)
    {
        if (url()->isValidUrl($path)) {
            return $path;
        }

        return url(admin_base_path($path), $parameters, $secure);
    }
}

if (! function_exists('admin_base_path')) {
    /**
     * Get admin base path.
     */
    function admin_base_path($path = '')
    {
        $prefix = '/'.trim(config('filament.path'), '/');

        $prefix = ($prefix == '/') ? '' : $prefix;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = parse_url($path, PHP_URL_PATH);
        }

        $path = trim($path, '/');

        if (is_null($path) || strlen($path) == 0) {
            return $prefix ?: '/';
        }

        if (Str::of($path)->is($prefix.'*')) {
            return $path;
        }

        if (Str::startsWith($path, [$prefix, trim($prefix, '/')])) {
            return $path;
        }

        return $prefix.'/'.$path;
    }
}
