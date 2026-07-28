<?php

namespace SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;

use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('Attach Role')
                ->action(function (Collection $records, array $data): void {
                    foreach ($records as $record) {
                        $record->roles()->sync($data['role']);
                        $record->save();
                    }
                })
                ->schema([
                    Select::make('role')
                        ->label(strval(__('filament-access-management::filament-access-management.field.role')))
                        ->options(Utils::getRoleModel()::query()->pluck('name', 'id'))
                        ->required(),
                ])->deselectRecordsAfterCompletion(),
        ];
    }
}
