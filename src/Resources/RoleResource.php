<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\Pages;
use SolutionForest\FilamentAccessManagement\Resources\RoleResource\RelationManagers;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class RoleResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(strval(__('filament-access-management::filament-access-management.field.name')))
                                    ->required(),
                                Forms\Components\TextInput::make('guard_name')
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.id'))),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-access-management::filament-access-management.field.name')),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label(__('filament-access-management::filament-access-management.field.guard_name')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.created_at'))),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
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
