<?php

namespace SolutionForest\FilamentAccessManagement\Support;

class Utils
{
    public static function getFilamentAuthGuard(): string
    {
        return (string) config('filament.auth.guard');
    }

    public static function getAdminRoleName(): string
    {
        return (string) config('filament-access-management.roles.admin.name');
    }

    public static function getUserModel(): string
    {
        return config('filament-access-management.models.User', 'SolutionForest\\FilamentAccessManagement\\Models\\User');
    }

    public static function getRoleModel(): string
    {
        return config('filament-access-management.model.Role', 'Spatie\\Permission\\Models\\Role');
    }

    public static function getPermissionModel(): string
    {
        return config('filament-access-management.models.Permission', 'Spatie\\Permission\\Models\\Permission');
    }
}
