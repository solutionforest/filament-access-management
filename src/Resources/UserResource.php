<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\CreateUser;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\EditUser;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\ListUsers;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\Pages\ViewUser;
use SolutionForest\FilamentAccessManagement\Resources\UserResource\RelationManagers\RolesRelationManager;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class UserResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.name')))
                            ->required(),

                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(table: static::getModel(), ignorable: fn ($record) => $record)
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.email'))),

                        TextInput::make('password')
                            ->same('passwordConfirmation')
                            ->password()
                            ->maxLength(255)
                            ->required(fn ($component, $get, $livewire, $model, $record, $set, $state) => $record === null)
                            ->dehydrateStateUsing(fn ($state) => ! empty($state) ? Hash::make($state) : '')
                            ->label(strval(__('filament-access-management::filament-access-management.field.user.password'))),

                        TextInput::make('passwordConfirmation')
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
                TextColumn::make('id')
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.id'))),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.name'))),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.email'))),

                IconColumn::make('email_verified_at')
                    ->icons([
                        'heroicon-o-check-circle',
                        'heroicon-o-x-circle' => fn ($state): bool => $state === null,
                    ])
                    ->colors([
                        'success',
                        'danger' => fn ($state): bool => $state === null,
                    ])
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.verified_at'))),

                TextColumn::make('roles.name')
                    ->badge()
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.roles'))),

                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.user.created_at'))),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.user') ?? parent::getNavigationIcon();
    }

    public static function getModel(): string
    {
        return Utils::getUserModel() ?? parent::getModel();
    }

    public static function getNavigationGroup(): ?string
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
