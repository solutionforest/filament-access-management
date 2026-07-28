<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Filament\Pages\Page;
use Filament\Panel;

class Error extends Page
{
    protected string $view = 'filament-access-management::pages.error';

    public $code;

    public function mount($code = null): void
    {
        $this->code = $code;
    }

    public static function getSlug(?Panel $panel = null): string
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
