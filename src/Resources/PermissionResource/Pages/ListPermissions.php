<?php

namespace SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;

use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        $roleOptions = [];

        foreach (Utils::getRoleModel()::query()->pluck('name', 'id') as $id => $name) {
            if (is_string($name)) {
                $roleOptions[$id] = $name;
            }
        }

        return [
            BulkAction::make('Attach Role')
                ->action(function (Collection $records, array $data): void {
                    foreach ($records as $record) {
                        if (! method_exists($record, 'roles')) {
                            continue;
                        }

                        $roles = $record->roles();

                        $role = $data['role'] ?? null;

                        if ($roles instanceof BelongsToMany && (
                            $role instanceof Model
                            || $role instanceof Collection
                            || is_array($role)
                            || is_int($role)
                            || is_string($role)
                        )) {
                            $roles->sync($role);
                        }

                        $record->save();
                    }
                })
                ->schema([
                    Select::make('role')
                        ->label(strval(__('filament-access-management::filament-access-management.field.role')))
                        ->options($roleOptions)
                        ->required(),
                ])->deselectRecordsAfterCompletion(),
        ];
    }
}
