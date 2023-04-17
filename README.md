# filament-access-management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/run-tests?label=tests)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/Check%20&%20fix%20styling?label=code%20style)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)


This is an authentication plugin for Filament Admin with Laravel-permission

## Installation

1. You can install the package via composer:
    ```bash
    composer require solution-forest/filament-access-management
    ```
    
2. Add the necessary trait to your User model:

    ```php

    use SolutionForest\FilamentAccessManagement\Concerns\FilamentUser;

    class User extends Authenticatable
    {
        use FilamentUser;
    }
    ```
    
3. **Clear your config cache**:
   ```bash
    php artisan optimize:clear
    # or
    php artisan config:clear
   ```

4. Then execute the following commands:
   ```bash
   php artisan filament-access-management:install
   ```
    This command will automatically run migration and create a **Super Admin User**, login information as following:

    - Email address: admin@*("slug" pattern of config("app.name"))*.com
    - Password: admin

5. In your config/app.php place this code in you providers section
    ``` php
    'providers' => [

        ...

        /*
            * Package Service Providers...
            */
        \SolutionForest\FilamentAccessManagement\FilamentAuthServiceProvider::class,

        ...

    ],
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

![image](./assets/images/user_1.png)
![image](./assets/images/user_2.png)
![image](./assets/images/role_1.png)
![image](./assets/images/role_2.png)
![image](./assets/images/permission_1.png)
![image](./assets/images/permission_2.png)

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
