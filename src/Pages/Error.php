<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Closure;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class Error extends Page
{
    protected static string $view = 'filament-access-management::pages.error';

    public $code;

    public function mount($code): void
    {
        $this->code = $code;
    }

    public static function getSlug(): string
    {
        return 'error';
    }

    public static function getRoutes(): Closure
    {
        return function () {
            $slug = static::getSlug();

            Route::get($slug. '/{code}', static::class)
                ->name($slug);
        };
    }

    protected function getTitle(): string
    {
        return '';
    }

    protected static function shouldRegisterNavigation(): bool
    {
        return false;
    }


    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'code' => $this->code ? intval($this->code) : 403,
            'errorMessage' => trans('filament-access-management::filament-access-management.errors.default'),
        ]);
    }
}
