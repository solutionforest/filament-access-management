<?php

// namespace SolutionForest\FilamentAccessManagement;

use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\FilamentAccessManagement;

if (! function_exists('filament_auth')) {
    function filament_auth(): FilamentAccessManagement
    {
        return app(FilamentAccessManagement::class);
    }
}

/**
 * @deprecated version 2.2.0
 */
if (! function_exists('admin_url')) {
    /**
     * Get admin url.
     *
     * @param  array<string, mixed>  $parameters
     */
    function admin_url(string $path = '', array $parameters = [], ?bool $secure = null): string
    {
        if (url()->isValidUrl($path)) {
            return $path;
        }

        return (string) url(admin_base_path($path), $parameters, $secure);
    }
}

/**
 * @deprecated version 2.2.0
 */
if (! function_exists('admin_base_path')) {
    /**
     * Get admin base path.
     */
    function admin_base_path(string $path = '', ?string $panel = null): string
    {
        $panelPath = (filament()->getPanel($panel) ?? filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel())->getPath();

        $configuredPath = config('filament.path');
        $basePath = is_string($configuredPath) ? $configuredPath : $panelPath;

        $prefix = '/'.trim($basePath, '/');

        $prefix = ($prefix == '/') ? '' : $prefix;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : '';
        }

        $path = trim($path, '/');

        if (strlen($path) === 0) {
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
