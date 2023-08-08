<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Filament\Forms;
use Filament\Actions\CreateAction;
use Guava\FilamentIconPicker\Forms\IconPicker;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentTree\Actions;
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
                ->label(__('filament-access-management::filament-access-management.field.menu.uri'))
                ->helperText('Relative path or external URL'),

            IconPicker::make('icon')
                ->label(__('filament-access-management::filament-access-management.field.menu.icon'))
                ->preload()
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])
                ->helperText('Menu item must contain the icon.')
                ->default(Utils::getFilamentDefaultIcon()),

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
            'parent_id' => $this->getModel()::selectArray(static::getMaxDepth() - 1),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    protected function configureCreateAction(CreateAction $action): CreateAction
    {
        $action = parent::configureCreateAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    protected function configureDeleteAction(Actions\DeleteAction $action): Actions\DeleteAction
    {
        $action = parent::configureDeleteAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    protected function configureEditAction(Actions\EditAction $action): Actions\EditAction
    {
        $action = parent::configureEditAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    public static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.menu') ?? parent::getNavigationIcon();
    }
}
