<?php

use SolutionForest\FilamentAccessManagement\Http\Middleware;
use SolutionForest\FilamentAccessManagement\Models;
use SolutionForest\FilamentAccessManagement\Pages;
use SolutionForest\FilamentAccessManagement\Resources;

return [
    /**
     * Except from authentication
     */
    'auth' => [
        'except' => [
            '/',
            '/login',
            '/error*',
        ],
    ],
    'filament' => [
        'path_permission_checking' => [
            /**
             * Determine the permissions if the `Action` have `url`
             */
            'action' => true,
        ],
        'middleware' => [
            'base' => [
                Middleware\Authenticate::class,
            ],
        ],
        'navigation' => [
            /**
             * Using db based filament navigation if true.
             */
            'enabled' => false,
            /**
             * Table name db based filament navigation.
             */
            'table_name' => 'filament_menu',
            /**
             * Filament Menu Model.
             */
            'model' => Models\Menu::class,
        ],
        'navigationIcon' => [
            /**
             * Default ICON of the page.
             */
            'default' => 'heroicon-o-document-text',
            'user' => 'heroicon-o-user',
            'role' => 'heroicon-o-user-group',
            'permission' => 'heroicon-o-lock-closed',
            'menu' => 'heroicon-o-bars-3-bottom-left',
        ],
        'pages' => [
            Pages\Menu::class,
        ],
        'resources' => [
            Resources\UserResource::class,
            Resources\RoleResource::class,
            Resources\PermissionResource::class,
        ]
    ],
    'roles' => [
        'super-admin' => [
            'name' => 'super-admin',
            'role_permissions' => [
                'users.*',
                'roles.*',
                'permissions.*',
                'menu.*',
            ],
        ],
    ],

    /**
     *
     * Default permissions to install
     *
     */

    'permissions' => [
        'users.*' => '/admin/users*',
        'users.viewAny' => '/admin/users',
        'users.view' => '/admin/users/*',
        'users.create' => '/admin/users/create',
        'users.update' => '/admin/users/*/edit',

        'roles.*' => '/admin/roles*',
        'roles.viewAny' => '/admin/users',
        'roles.view' => '/admin/users/*',
        'roles.create' => '/admin/users/create',
        'roles.update' => '/admin/users/*/edit',

        'permissions.*' => '/admin/permissions*',
        'permissions.viewAny' => '/admin/permissions',
        'permissions.view' => '/admin/permissions/*',
        'permissions.create' => '/admin/permissions/create',
        'permissions.update' => '/admin/permissions/*/edit',

        'menu.*' => '/admin/menu*',
        'menu.viewAny' => '/admin/menu',
        'menu.view' => '/admin/menu/*',
        'menu.create' => '/admin/menu/create',
        'menu.update' => '/admin/menu/*/edit',
    ],

    /**
     *
     * Cache settings
     *
     */
    'cache' => [
        /**
         *
         * User's permission cache settings
         *
         */
        'user_permissions' => [
            /*
            * By default all permissions are cached for 24 hours to speed up performance.
            * When permissions or roles are updated the cache is flushed automatically.
            */

            'expiration_time' => \DateInterval::createFromDateString('24 hours'),

            /*
            * The cache key used to store all permissions.
            */

            'key_prefix' => 'user_spatie.permission.cache',

            /*
            * You may optionally indicate a specific cache driver to use for permission and
            * role caching using any of the `store` drivers listed in the cache.php config
            * file. Using 'default' here means to use the `default` set in cache.php.
            */

            'store' => 'default',

            'tag' => 'user_permissions',
        ],

        /**
         *
         * Filament navigation cache settings
         *
         */
        'navigation' => [
            'expiration_time' => \DateInterval::createFromDateString('24 hours'),
            'key' => 'filament_navigation',
        ]
    ],
];
