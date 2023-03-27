<?php

namespace SolutionForest\FilamentAccessManagement\Resources\RoleResource\RelationManagers;

use App\Models\TranslatableLink;
use App\Traits;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
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

                    Forms\Components\TextInput::make('http_path')
                        ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

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
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }

    public function afterSave(): void
    {
        logger(__METHOD__);
        if (! is_a($this->record, Utils::getPermissionModel())) {
            return;
        }

        FilamentAuthenticate::clearPermissionCache();
    }
}
