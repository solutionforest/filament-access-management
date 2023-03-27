<?php

use SolutionForest\FilamentAccessManagement\Http\Middleware;
use SolutionForest\FilamentAccessManagement\Models;
use SolutionForest\FilamentAccessManagement\Resources;

return [
    'auth' => [
        'except' => [
            '/',
            '/login',
            '/error*',
        ],
    ],
    'filament' => [
        'middleware' => [
            'base' => [
                Middleware\Authenticate::class,
            ]
        ],
        'permission' => [
            'enable'            => true,
        ],
        'secure'                => false,
    ],
    'navigationIcon' => [
        'user'                  => 'heroicon-o-user',
        'role'                  => 'heroicon-o-user-group',
        'permission'            => 'heroicon-o-lock-closed',
    ],
    'resources' => [
        'UserResource'          => Resources\UserResource::class,
        'RoleResource'          => Resources\RoleResource::class,
        'PermissionResource'    => Resources\PermissionResource::class,
    ],
    'roles' => [
        'admin' => [
            'name'              => 'super-admin',
            'role_permissions'  => [
                'users.*',
                'roles.*',
                'permissions.*',
            ]
        ]
    ],
    'permissions' => [
        'users.*'               => '/admin/users/*',
        'users.viewAny'         => '/admin/users',
        'users.view'            => '/admin/users/*',
        'users.create'          => '/admin/users/create',
        'users.update'          => '/admin/users/*/edit',
        'users.delete'          => '/admin/users/delete',

        'roles.*'               => '/admin/roles/*',
        'roles.viewAny'         => '/admin/users',
        'roles.view'            => '/admin/users/*',
        'roles.create'          => '/admin/users/create',
        'roles.update'          => '/admin/users/*/edit',
        'roles.delete'          => '/admin/users/delete',

        'permissions.*'         => '/admin/permissions/*',
        'permissions.viewAny'   => '/admin/permissions',
        'permissions.view'      => '/admin/permissions/*',
        'permissions.create'    => '/admin/permissions/create',
        'permissions.update'    => '/admin/permissions/*/edit',
        'permissions.delete'    => '/admin/permissions/delete',
    ],
    'cache' => [
        'store'                 => 'array',
        'tags' => [
            'user_permissions',
        ],
        'user_permissions' => [
            /*
            * By default all permissions are cached for 24 hours to speed up performance.
            * When permissions or roles are updated the cache is flushed automatically.
            */

            'expiration_time'   => \DateInterval::createFromDateString('24 hours'),

            /*
            * The cache key used to store all permissions.
            */

            'key_prefix'        => 'user_spatie.permission.cache',

            /*
            * You may optionally indicate a specific cache driver to use for permission and
            * role caching using any of the `store` drivers listed in the cache.php config
            * file. Using 'default' here means to use the `default` set in cache.php.
            */

            'store'         => 'default',
        ],
    ],
];
