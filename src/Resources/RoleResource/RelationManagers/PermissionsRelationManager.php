<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                ]),
            ]);
    }

    public function table(Table $table): Table
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
            ->headerActions([
                CreateAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
                AttachAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
            ])
            ->recordActions([
                DetachAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
            ])
            ->toolbarActions([
                DetachBulkAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
            ]);
    }

    protected static function afterSave(): void
    {
        FilamentAuthenticate::clearPermissionCache();
    }
}
