<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                    Forms\Components\TextInput::make('guard_name')
                        ->required()
                        ->label(strval(__('filament-access-management::filament-access-management.field.guard_name')))
                        ->default(config('auth.defaults.guard')),

                    Forms\Components\Select::make('http_path')
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.id'))),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label(strval(__('filament-access-management::filament-access-management.field.guard_name'))),

                Tables\Columns\TextColumn::make('http_path')
                    ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.created_at'))),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
                Tables\Actions\AttachAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->after(function () {
                        static::afterSave();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
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
