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
                ->options($this->getModel()::selectArray(static::getMaxDepth()))
                ->default(FilamentTreeHelper::defaultParentId())
                ->required(),
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
}
