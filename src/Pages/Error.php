<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Closure;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

class Error extends Page
{
    protected static string $view = 'filament-access-management::pages.error';

    public $code;

    public function mount($code = null): void
    {
        $this->code = $code;
    }

    public static function getSlug(): string
    {
        return 'error/{code?}';
    }

    public function getTitle(): string
    {
        return '';
    }

    public static function shouldRegisterNavigation(): bool
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
