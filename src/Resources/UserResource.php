<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Illuminate\Support\Facades\Hash;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\RelationManagers;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class UserResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.name')))
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(table: static::getModel(), ignorable: fn ($record) => $record)
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.email'))),

                        Forms\Components\TextInput::make('password')
                            ->same('passwordConfirmation')
                            ->password()
                            ->maxLength(255)
                            ->required(fn ($component, $get, $livewire, $model, $record, $set, $state) => $record === null)
                            ->dehydrateStateUsing(fn ($state) => ! empty($state) ? Hash::make($state) : '')
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.password'))),

                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->password()
                            ->dehydrated(false)
                            ->maxLength(255)
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.confirm_password'))),

                        // Forms\Components\Select::make('roles')
                        //     ->multiple()
                        //     ->relationship('roles', 'name')
                        //     ->preload()
                        //     ->label(strval(__('filament-access-management::filament-access-management.field.user.roles'))),
                    ])->columns(2),
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
            RelationManagers\RolesRelationManager::class,
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

    protected static function getNavigationIcon(): string
    {
        return config('filament-access-management.navigationIcon.user') ?? parent::getNavigationIcon();
    }

    public static function getModel(): string
    {
        return Utils::getUserModel() ?? parent::getModel();
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
}
