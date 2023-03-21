<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\RelationManagers;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function getModel(): string
    {
        return config('filament-access-management::filament-access-management.models.User') ?? parent::getModel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    public static function getLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.user'));
    }

    public static function getPluralLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.users'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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
                    ->searchable()
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.name'))),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.email'))),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->options([
                        'heroicon-o-check-circle',
                        'heroicon-o-x-circle' => fn ($state): bool => $state === null,
                    ])
                    ->colors([
                        'success',
                        'danger' => fn ($state): bool => $state === null,
                    ])
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.verified_at'))),
                Tables\Columns\TagsColumn::make('roles.name')
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.roles'))),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.created_at'))),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
