<p align="center">
  <a href="https://solutionforest.com" target="_blank">
    <img src="https://github.com/solutionforest/.github/blob/main/docs/images/sf.png?raw=true" width="200" alt="Solution Forest">
  </a>
</p>

<h1 align="center">Filament Access Management</h1>

<p align="center">
  Role & permission management for <a href="https://filamentphp.com">Filament</a>, powered by
  <a href="https://spatie.be/docs/laravel-permission">spatie/laravel-permission</a> — with a
  path-based access gate, a database-driven navigation menu, and a one-command super-admin setup.
</p>

<p align="center">
  <a href="https://packagist.org/packages/solution-forest/filament-access-management"><img src="https://img.shields.io/packagist/v/solution-forest/filament-access-management.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://github.com/solutionforest/filament-access-management/actions/workflows/quick-test.yml"><img src="https://github.com/solutionforest/filament-access-management/actions/workflows/quick-test.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/solution-forest/filament-access-management"><img src="https://img.shields.io/packagist/dt/solution-forest/filament-access-management.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/solution-forest/filament-access-management.svg?style=flat-square" alt="License"></a>
</p>

---

## ✨ Features

- 👥 **Users, Roles & Permissions** resources ready to use out of the box.
- 🛡️ **Path-based access control** — bind permissions to HTTP paths (`/users`, `/users/*/edit`), methods, or route aliases.
- 👑 **Super-admin bypass** — a configurable super-admin role that skips every gate.
- 🌳 **Database-driven navigation menu** — optional tree menu that can replace Filament's default navigation.
- ⚡ **One-command setup** — `filament-access-management:install` scaffolds everything and creates your first super admin.
- 🔀 **Filament v3, v4 & v5** support (see the compatibility table below).

## 📋 Compatibility

| Plugin version | Filament version |
| -------------- | ---------------- |
| 1.x            | 2.x              |
| 2.x            | 3.x              |
| 3.x            | 4.x / 5.x        |

## 📦 Installation

1. Ensure you have already installed a [Filament panel](https://filamentphp.com/docs/panels/installation).
2. Install the package via Composer:

    ```bash
    composer require solution-forest/filament-access-management
    ```

3. Add the necessary trait to your `User` model:

    ```php
    use SolutionForest\FilamentAccessManagement\Concerns\FilamentUserHelpers;

    class User extends Authenticatable
    {
        use FilamentUserHelpers;
    }
    ```

    > **Panel access (production):** the trait provides the permission helpers but does **not**
    > implement Filament's `FilamentUser` contract. Outside the `local` environment Filament denies
    > panel access (HTTP 403) to any user whose model doesn't implement it, so also add
    > `canAccessPanel()` — see the [Upgrade Guide](#-upgrade-guide).

4. Clear your config cache:

    ```bash
    php artisan optimize:clear
    # or
    php artisan config:clear
    ```

5. Register the plugin in your panel provider (**required from v2.x onwards**):

    ```php
    use SolutionForest\FilamentAccessManagement\FilamentAccessManagementPlugin;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugin(FilamentAccessManagementPlugin::make());
    }
    ```

6. Run the installer:

    ```bash
    php artisan filament-access-management:install
    ```

    If you don't already have a user named `admin`, this creates a **Super Admin User**:

    | Field    | Value                                                |
    | -------- | ---------------------------------------------------- |
    | Name     | `admin`                                              |
    | Email    | `admin@("slug" pattern from config("app.name")).com` |
    | Password | `admin`                                              |

    You can also create one anytime with:

    ```bash
    php artisan make:super-admin-user
    ```

7. If you are upgrading data from **before v2.2.0**, run:

    ```bash
    php artisan filament-access-management:upgrade
    ```

## 🗂️ Publish Configs, Views, Translations and Migrations

```bash
php artisan vendor:publish --tag="filament-access-management-config"
php artisan vendor:publish --tag="filament-access-management-views"
php artisan vendor:publish --tag="filament-access-management-translations"
php artisan vendor:publish --tag="filament-access-management-migrations"
```

Then run the migrations:

```bash
php artisan migrate
```

## 🔼 Upgrade Guide

> [!CAUTION]
>
> ## ⚠️ BACK UP YOUR DATABASE FIRST ⚠️
>
> Upgrading runs migrations and the `filament-access-management:upgrade` command **rewrites menu
> data in place**. **Always take a full backup of your database (and code) before you start.**
> Test the upgrade on a staging copy first, and never run it against production without a verified,
> restorable backup. This operation can modify or delete rows and is **not automatically reversible**.

This plugin follows the Filament major it targets. Upgrade the plugin **together with** Filament
in a single Composer command, because the `2.x` line pins `filament/filament: ^3.0` and will block
a Filament v4/v5 install. The steps below were verified against a real Laravel app upgraded
**in place** (same database file) from Filament v3 → v4 → v5.

### Requirements on your `User` model (all versions)

Add the `FilamentUser` contract and `canAccessPanel()` so the panel isn't 403'd outside `local`:

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

### Filament v3 (plugin 2.x) → Filament v4 (plugin 3.x)

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

### Filament v4 → Filament v5 (both on plugin 3.x)

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
  Eloquent), call
  `\SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate::clearPermissionCache()`
  so the change takes effect. The plugin's own Role/Permission pages clear it automatically on save.
- **After each upgrade, verify:** the login page renders, the super admin can reach the User / Role /
  Permission resources and the Menu page, a normal user is denied a protected page without the
  matching permission and allowed with it, and `storage/logs/laravel.log` is clean.

## 🚀 Usage

Upon installation, **Menu**, **Users**, **Roles** and **Permissions** pages are created. Each user
has roles, and each role has permissions.

<details open>
<summary><b>Menu, Users, Roles & Permissions</b></summary>

![Overview](https://user-images.githubusercontent.com/73818060/232434966-91ab94fe-620a-4894-8632-dbe5e535e5ae.png)

**Manage Menu**

![Manage Menu](https://user-images.githubusercontent.com/73818060/232438118-0b4089e7-4ff0-40b8-93b1-c6d4c089ef14.png)

**Manage Users and their roles**

![Manage Users](https://user-images.githubusercontent.com/73818060/232437828-73039db1-8976-4a23-a14d-2943d9495a47.png)
![User roles](https://user-images.githubusercontent.com/73818060/232437890-2db887e1-dcbb-4d96-b072-365720be66d7.png)

**Manage Roles and their permissions**

![Manage Roles](https://user-images.githubusercontent.com/73818060/232438496-002b56d6-db98-4672-82cc-efcfc06fba9e.png)
![Role permissions](https://user-images.githubusercontent.com/73818060/232438548-29b655bc-d683-4924-90b7-6ba25991d7ff.png)

**Manage Permissions**

![Manage Permissions](https://user-images.githubusercontent.com/73818060/232438632-e5d9a5e5-7ef5-4ca5-a330-37948acd9748.png)
![Permission detail](https://user-images.githubusercontent.com/73818060/232438719-fc2bca0b-7233-4aae-bf87-9c1d8524e42d.png)

</details>

### Routing control

Permissions and routes are bound together. On the edit-permission page, set the routes a
permission can access: choose the method in the **HTTP method** select box, and fill the accessible
path in **HTTP path**.

| Goal                                  | HTTP path                             |
| ------------------------------------- | ------------------------------------- |
| Access`GET /admin/users`              | `/users` (with `HTTP method` = `GET`) |
| Access everything under`/admin/users` | `/users*`                             |
| Access the edit page only             | `/users/*/edit`                       |
| Different method per path             | `GET:users/*`                         |
| Use a route alias                     | `admin.users.show`                    |

### Super Administrator

Create a super admin user:

```bash
php artisan make:super-admin-user
```

Check a permission:

```php
// Check by permission's name
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::check($name);

// Check by http_path
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::checkPermission($path);
```

Get the current user:

```php
\SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate::user();
```

## 🧩 Advanced Usage

By default the plugin's menu **co-exists** with Filament's native navigation. To replace the native
navigation with the database-driven menu from this package, edit
`/config/filament-access-management.php` and set `filament.navigation.enabled => true`:

```php
'filament' => [
    // ...
    'navigation' => [
        // Use db-based Filament navigation when true.
        'enabled'    => true,
        // Table name for the db-based navigation.
        'table_name' => 'filament_menu',
        // Filament Menu model.
        'model'      => Models\Menu::class,
    ],
    // ...
],
```

## 🧪 Testing

```bash
composer test
```

The [`quick-test`](.github/workflows/quick-test.yml) CI workflow runs the Pest suite against
**both Filament v4 and Filament v5** (PHP 8.4, Ubuntu) on every push, PR and tag. A tag whose
suite fails is deleted automatically.

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 👏 Credits

- [Carly](https://github.com/n/a)
- [All Contributors](../../contributors)

---

## 🌲 About Solution Forest

<p>
  <a href="https://solutionforest.com" target="_blank">
    <img src="https://github.com/solutionforest/.github/blob/main/docs/images/sf.png?raw=true" width="160" alt="Solution Forest">
  </a>
</p>

[Solution Forest](https://solutionforest.com) is a web development agency based in Hong Kong. We help
customers solve their problems — and we ❤️ open source.

**Our products**

- [Vxero Neo](https://neo.vxero.dev) — Deploy to any VPS from your terminal.
- [Vxero](https://vxero.com) — Deploy without DevOps complexity.
- [InspireCMS](https://inspirecms.net) — A full-featured Laravel CMS with everything you need out of the box.
- [Filaletter](https://filaletter.solutionforest.net) — Filament newsletter plugin.
- [Website CMS Management](https://filamentphp.com/plugins/solution-forest-cms-website) — A hands-on Filament CMS plugin.

**Open source**

- [ForgeDesk](https://github.com/solutionforest/ForgeDesk) — Zero-config developer tools. One click to install, one click to run.
- [Watchdog](https://github.com/solutionforest/Watchdog) — An uptime monitor desktop application.
- [ocpp-php](https://github.com/solutionforest/ocpp-php) — PHP implementation of the Open Charge Point Protocol (OCPP).
- [Filament plugins](https://github.com/solutionforest?q=filament) — Our Filament plugin collection.

You can also sponsor our open source work [via GitHub Sponsors](https://github.com/sponsors/solutionforest). 💚

## 📄 License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
