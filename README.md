
# filament-access-management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)
[![quick-test](https://github.com/solutionforest/filament-access-management/actions/workflows/quick-test.yml/badge.svg)](https://github.com/solutionforest/filament-access-management/actions/workflows/quick-test.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)


This is an authentication plugin for Filament Admin with Laravel-permission

> **Tests:** the [`quick-test`](.github/workflows/quick-test.yml) workflow runs the Pest suite on every push and every tag (PHP 8.4, Ubuntu). Latest local run on Filament v5.7.3: **111 passed**. A tag that fails the suite is deleted automatically.

## Compatibility

| Plugin version | Filament version |
| --------------- | ---------------- |
| 1.x             | 2.x               |
| 2.x             | 3.x               |
| 3.x             | 4.x / 5.x         |

## Upgrade Guide

This plugin follows the Filament major it targets. Upgrade the plugin **together with**
Filament in a single Composer command, because the `2.x` line pins `filament/filament: ^3.0`
and will block a Filament v4/v5 install. The steps below were verified against a real Laravel
app upgraded **in place** (same database file) from Filament v3 → v4 → v5.

### Requirements on your `User` model (all versions)

The `FilamentUserHelpers` trait provides the permission helpers, but it does **not** implement
Filament's `FilamentUser` contract. Outside the `local` environment Filament denies panel
access (HTTP 403) to any user whose model does not implement it, so add `canAccessPanel()`
yourself:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use SolutionForest\FilamentAccessManagement\Concerns\FilamentUserHelpers;

class User extends Authenticatable implements FilamentUser
{
    use FilamentUserHelpers;

    public function canAccessPanel(Panel $panel): bool
    {
        // Per-page permissions are still enforced by this plugin's middleware.
        return true;
    }
}
```

### Upgrading from Filament v3 (plugin 2.x) to Filament v4 (plugin 3.x)

1. Move Filament and the plugin together:
   ```bash
   composer require "filament/filament:^4.0" "solution-forest/filament-access-management:^3.0" -W
   ```
   This also moves `solution-forest/filament-tree` (2 → 3) and `guava/filament-icon-picker` (2 → 3).
2. Follow the [official Filament v3 → v4 upgrade guide](https://filamentphp.com/docs/4.x/upgrade-guide)
   for **your own** app code (namespace changes, `Form` → `Schema`, action namespaces).
3. Run migrations (no new column is added — `is_filament_panel` already ships in the
   `upgrade_menu_table` migration) and rewrite any legacy `/admin/...` menu URIs:
   ```bash
   php artisan migrate
   php artisan filament-access-management:upgrade
   ```
   `filament-access-management:upgrade` strips the `/admin` prefix from menu `uri`s and sets
   `is_filament_panel = true`. It only touches rows whose `uri` is `/admin` or `/admin/%` **and**
   `is_filament_panel = false`, so external URLs are left untouched and the command is idempotent
   (a re-run with nothing to migrate exits cleanly).

### Upgrading from Filament v4 to Filament v5 (both on plugin 3.x)

Filament v5 requires **Laravel 12** (and pulls in **Livewire 4**), so bump them in the same step:

```bash
composer require "filament/filament:^5.0" "laravel/framework:^12.0" \
  "solution-forest/filament-tree:^4.0" "solution-forest/filament-access-management:^3.0" -W
```

Then follow the official Filament v4 → v5 and Laravel 11 → 12 upgrade guides for your own code,
run `php artisan migrate` (no-op) and re-run `php artisan filament-access-management:upgrade`.

### Notes

- **Clearing the permission cache:** the plugin caches per-user permissions. When you change a
  user's roles/permissions **outside** the plugin's own resource pages (e.g. via a seeder or
  Eloquent), call `\SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate::clearPermissionCache()`
  so the change takes effect. The plugin's own Role/Permission pages clear it automatically on save.
- After each upgrade, verify: the login page renders, the super admin can reach the User / Role /
  Permission resources and the Menu page, a normal user is denied a protected page without the
  matching permission and allowed with it, and `storage/logs/laravel.log` is clean.

## Installation

1. Ensure you have already installed the Filament panel.
2. You can install the package via composer:
    ```bash
    composer require solution-forest/filament-access-management
    ```
    
3. Add the necessary trait to your User model:

    ```php

    use SolutionForest\FilamentAccessManagement\Concerns\FilamentUserHelpers;

    class User extends Authenticatable
    {
        use FilamentUserHelpers;
    }
    ```
    
4. **Clear your config cache**:
   ```bash
    php artisan optimize:clear
    # or
    php artisan config:clear
   ```

5. Register the plugin in your Panel provider:
   > **Important:  Register the plugin in your Panel provider after version 2.x**
   ``` bash
    use SolutionForest\FilamentAccessManagement\FilamentAccessManagementPanel;
 
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugin(FilamentAccessManagementPanel::make());
    }
   ```

6. Then execute the following commands:
   ```bash
   php artisan filament-access-management:install
   ```
   If you don't already have a user named `admin`, this command creates a **Super Admin User** with the following credentials:

    - Name: admin
    - E-mail address: admin@("slug" pattern from config("app.name")).com
    - Password: admin

    You can also create the super admin user with:

    ```bash
    php artisan make:super-admin-user
    ```

6. Call upgrade command to upgrade data after version **2.2.0**
    ```bash
    php artisan filament-access-management:upgrade
    ```


## Publish Configs, Views, Translations and Migrations

You can publish the configs, views, translations and migrations with:

```bash
php artisan vendor:publish --tag="filament-access-management-config"

php artisan vendor:publish --tag="filament-access-management-views"

php artisan vendor:publish --tag="filament-access-management-translations"

php artisan vendor:publish --tag="filament-access-management-migrations"
```

## Migration

```bash
php artisan migrate
```

## Usage

Upon installation, "Menu", "Users", "Roles" and "Permissions" pages will be created. Each user have roles and each role have permissions.

![image](https://user-images.githubusercontent.com/73818060/232434966-91ab94fe-620a-4894-8632-dbe5e535e5ae.png)

Manage Menu:
![image](https://user-images.githubusercontent.com/73818060/232438118-0b4089e7-4ff0-40b8-93b1-c6d4c089ef14.png)

Manage Users and their roles:
![image](https://user-images.githubusercontent.com/73818060/232437828-73039db1-8976-4a23-a14d-2943d9495a47.png)
![image](https://user-images.githubusercontent.com/73818060/232437890-2db887e1-dcbb-4d96-b072-365720be66d7.png)

Manage Roles and their permissions:
![image](https://user-images.githubusercontent.com/73818060/232438496-002b56d6-db98-4672-82cc-efcfc06fba9e.png)
![image](https://user-images.githubusercontent.com/73818060/232438548-29b655bc-d683-4924-90b7-6ba25991d7ff.png)

Manage Permissions:
![image](https://user-images.githubusercontent.com/73818060/232438632-e5d9a5e5-7ef5-4ca5-a330-37948acd9748.png)
![image](https://user-images.githubusercontent.com/73818060/232438719-fc2bca0b-7233-4aae-bf87-9c1d8524e42d.png)

## Routing control

In this plugin, permissions and routes are bound together, set the routes that the current permissions can access in the edit permissions page, select the method to access the routes in the `HTTP method` select box, and fill in the path that can be accessed in the `HTTP path`.

For example, if you want to add a permission, which can access the path `/admin/users` by `GET`, then `HTTP method` select `GET`, and `HTTP path` fill in `/users`.


If you want to access all the paths prefixed with `/admin/users`, then `HTTP path` fill in `/users*`; if you want to access the edit page, then `HTTP path` fill in `/users/*/edit`; if the method of each path in multiple paths is different, then `HTTP path` fill in `GET:users/*'. `.


If the above method is not sufficient, `HTTP path` also supports **routing aliases**, such as `admin.users.show`.

## Super Administrator

Create super admin user:

```bash

php artisan make:super-admin-user

```

Check permission:
```bash

# Check by permission's name
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::check($name)

# Check by http_path
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::checkPermission($path)

```

Get current user:
``` bash

\SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate::user();

```

## Advance Usage

In default, the menu created will co-exist with the original menu of filament. To override the original menu with the menu from this package, modify `/config/filament-access-management.php` as following:

1. Set ```filament.navigation.enabled => true```

``` php

    'filament' => [
        ...
        'navigation' => [
            /**
             * Using db based filament navigation if true.
             */
            'enabled' => true,
            /**
             * Table name db based filament navigation.
             */
            'table_name' => 'filament_menu',
            /**
             * Filament Menu Model.
             */
            'model' => Models\Menu::class,
        ]
        ...
    ]

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Carly](https://github.com/n/a)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
