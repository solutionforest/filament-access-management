# filament-access-management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/run-tests?label=tests)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/Check%20&%20fix%20styling?label=code%20style)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)

<!--delete-->
---
This repo can be used to scaffold a Filament plugin. Follow these steps to get started:

1. Press the "Use this template" button at the top of this repo to create a new repo with the contents of this filament-access-management.
2. Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
3. Make something great!
---
<!--/delete-->

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require solution-forest/filament-access-management
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-access-management-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-access-management-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-access-management-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filament-access-management = new SolutionForest\FilamentAccessManagement();
echo $filament-access-management->echoPhrase('Hello, SolutionForest!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [:author_name](https://github.com/:author_username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
