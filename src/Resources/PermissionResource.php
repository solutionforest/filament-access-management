<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\CreatePermission;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\EditPermission;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\ListPermissions;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages\ViewPermission;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\RelationManagers\RolesRelationManager;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class PermissionResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                            TextInput::make('guard_name')
                                ->required()
                                ->label(strval(__('filament-access-management::filament-access-management.field.guard_name')))
                                ->default(config('auth.defaults.guard')),

                            Select::make('http_path')
                                ->options(FilamentAuthenticate::allRoutes())
                                ->searchable()
                                ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

                            // Forms\Components\BelongsToManyMultiSelect::make('roles')
                            //     ->label(strval(__('filament-access-management::filament-access-management.field.roles')))
                            //     ->relationship('roles', 'name')
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
                    ->sortable()
                    ->searchable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                TextColumn::make('guard_name')
                    ->label(strval(__('filament-access-management::filament-access-management.field.guard_name'))),

                TextColumn::make('http_path')
                    ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

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
            RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
            'view' => ViewPermission::route('/{record}'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.permission') ?? parent::getNavigationIcon();
    }

    public static function getModel(): string
    {
        return Utils::getPermissionModel() ?? parent::getModel();
    }

    public static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    public static function getLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.permission'));
    }

    public static function getPluralLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.permissions'));
    }
}
