<?php

// config for SolutionForest/FilamentAccessManagement
return [
    'navigationIcon' => [
        'User'                  => 'heroicon-o-user',
        'Role'                  => 'heroicon-o-user-group',
        'Permission'            => 'heroicon-o-lock-closed',
    ],
    'models' => [
        'User'                  => \SolutionForest\FilamentAccessManagement\Models\User::class,
        'Role'                  => \Spatie\Permission\Models\Role::class,
        'Permission'            => \Spatie\Permission\Models\Permission::class,
    ],
    'resources' => [
        'UserResource'          => \SolutionForest\FilamentAccessManagement\Resources\UserResource::class,
        'RoleResource'          => \SolutionForest\FilamentAccessManagement\Resources\RoleResource::class,
        'PermissionResource'    => \SolutionForest\FilamentAccessManagement\Resources\PermissionResource::class,
    ],
    'roles' => [
        'admin' => [
            'name'              => 'admin',
        ],
    ],
    'table_names' => [
        'users'                 => 'users',
    ],
];
