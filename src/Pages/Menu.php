<?php

namespace SolutionForest\FilamentAccessManagement\Pages;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
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

    /** @var array<string, array<int|string, string>>|null */
    protected ?array $cachedOptions = null;

    public static function getMaxDepth(): int
    {
        return 2;
    }

    /**
     * @return class-string<\SolutionForest\FilamentAccessManagement\Models\Menu>
     */
    public function getModel(): string
    {
        return Utils::getMenuModel();
    }

    /**
     * @return array<int, Component>
     */
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

    /**
     * @return array<int|string, string>
     */
    protected function getCachedOption(string $name): array
    {
        return array_filter($this->getCachedOptions()[$name] ?? [], 'is_string');
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    protected function getCachedOptions(): array
    {
        if ($this->cachedOptions) {
            return $this->cachedOptions;
        }

        $options = [];

        foreach ($this->getModel()::selectArray(static::getMaxDepth() - 1) as $key => $label) {
            if (is_string($label)) {
                $options[$key] = $label;
            }
        }

        return $this->cachedOptions ??= [
            'parent_id' => $options,
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
        return Utils::resolveNavigationIcon('menu', parent::getNavigationIcon());
    }
}
