<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\CreateRole;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\EditRole;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\ListRoles;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages\ViewRole;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\RelationManagers\PermissionsRelationManager;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class RoleResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(strval(__('filament-access-management::filament-access-management.field.name')))
                                    ->required(),
                                TextInput::make('guard_name')
                                    ->label(strval(__('filament-access-management::filament-access-management.field.guard_name')))
                                    ->required()
                                    ->default(Utils::getFilamentAuthGuard()),
                                // Forms\Components\Select::make('permissions')
                                //     ->multiple()
                                //     ->label(strval(__('filament-access-management::filament-access-management.field.permissions')))
                                //     ->relationship('permissions', 'name')
                                //     ->preload()
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.id'))),

                TextColumn::make('name')
                    ->label(__('filament-access-management::filament-access-management.field.name')),

                TextColumn::make('guard_name')
                    ->label(__('filament-access-management::filament-access-management.field.guard_name')),

                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.created_at'))),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ViewRole::route('/{record}'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.role') ?? parent::getNavigationIcon();
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel() ?? parent::getModel();
    }

    public static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    public static function getLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.role'));
    }

    public static function getPluralLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.roles'));
    }
}
