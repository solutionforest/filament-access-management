<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Guava\IconPicker\Forms\Components\IconPicker;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentTree\Actions\DeleteAction;
use SolutionForest\FilamentTree\Actions\EditAction;
use SolutionForest\FilamentTree\Actions\ViewAction;
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
            TextInput::make('title')
                ->label(__('filament-access-management::filament-access-management.field.title'))
                ->required(),

            TextInput::make('uri')
                ->label(__('filament-access-management::filament-access-management.field.menu.uri'))
                ->helperText('Relative path or external URL'),

            Toggle::make('is_filament_panel')
                ->label(__('filament-access-management::filament-access-management.field.menu.is_filament_panel'))
                ->inlineLabel(),

            IconPicker::make('icon')
                ->label(__('filament-access-management::filament-access-management.field.menu.icon'))
                ->iconsSearchResults()
                ->helperText('Menu item must contain the icon.')
                ->default(Utils::getFilamentDefaultIcon())
                ->extraAttributes((['class' => 'guava-filament-icon-picker'])),

            Select::make('parent_id')
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

    protected function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()->iconButton();
    }

    protected function getEditAction(): EditAction
    {
        return EditAction::make()->iconButton();
    }

    protected function getViewAction(): ViewAction
    {
        return ViewAction::make()->iconButton();
    }

    protected function configureCreateAction(CreateAction $action): CreateAction
    {
        $action = parent::configureCreateAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    protected function configureDeleteAction(DeleteAction $action): DeleteAction
    {
        $action = parent::configureDeleteAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    protected function configureEditAction(EditAction $action): EditAction
    {
        $action = parent::configureEditAction($action);

        // Refresh navigation
        $action->successRedirectUrl(static::getUrl());

        return $action;
    }

    public static function getNavigationLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.menu'));
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
