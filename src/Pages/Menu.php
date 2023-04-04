<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Filament\Forms;
use Guava\FilamentIconPicker\Forms\IconPicker;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentTree\Components\Tree;
use SolutionForest\FilamentTree\Pages\TreePage;
use SolutionForest\FilamentTree\Support\Utils as FilamentTreeHelper;

class Menu extends TreePage
{
    protected static ?string $slug = 'menu';

    protected ?array $cachedOptions = null;

    public static function getMaxDepth(): int
    {
        return 2;
    }

    public function getModel(): string
    {
        return Utils::getMenuModel();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label(__('filament-access-management::filament-access-management.field.title'))
                ->required(),

            Forms\Components\TextInput::make('uri')
                ->label(__('filament-access-management::filament-access-management.field.menu.uri')),

            IconPicker::make('icon')
                ->label(__('filament-access-management::filament-access-management.field.menu.icon'))
                ->preload()
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])
                ->sets('heroicons')
                ->default(Utils::getFilamentDefaultIcon())
                ->required(),

            Forms\Components\Select::make('parent_id')
                ->label(__('filament-access-management::filament-access-management.field.menu.parent'))
                ->options($this->getCachedOption('parent_id'))
                ->default(FilamentTreeHelper::defaultParentId())
                ->required(),
        ];
    }

    protected function getCachedOption($name): array
    {
        return data_get($this->getCachedOptions(), $name, []);
    }

    protected function getCachedOptions(): array
    {
        if ($this->cachedOptions) {
            return $this->cachedOptions;
        }

        return $this->cachedOptions ??= [
            'parent_id' => $this->getModel()::selectArray(static::getMaxDepth()),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    protected static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    protected static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.menu') ?? parent::getNavigationIcon();
    }
}
