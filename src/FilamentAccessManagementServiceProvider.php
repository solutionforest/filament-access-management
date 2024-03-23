<?php

namespace SolutionForest\FilamentAccessManagement;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use SolutionForest\FilamentAccessManagement\Database\Seeders;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAccessManagementServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-access-management';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    // ->askToRunMigrations()
                    ->endWith(function (InstallCommand $command) {
                        $command->call('migrate');

                        $classes = [
                            Seeders\UserPermissionSeeder::class,
                            Seeders\NavigationSeeder::class,
                        ];
                        foreach ($classes as $class) {
                            $params = [
                                '--class' => $class,
                            ];

                            $command->call('db:seed', $params);
                        }
                        // Clear cache
                        Facades\FilamentAuthenticate::clearPermissionCache();
                        Facades\FilamentAuthenticate::menu()->clearCache();
                    });
            });
    }

    protected function getCommands(): array
    {
        return [
            Commands\MakeSuperAdminUser::class,
            Commands\MakeMenu::class,
        ];
    }

    protected function getMigrations(): array
    {
        return [
            'create_filament_admin_tables',
        ];
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->scoped('filament-access-management', function (): FilamentAccessManagement {
            return app(FilamentAccessManagement::class);
        });

        Config::push('app.providers', \Spatie\Permission\PermissionServiceProvider::class);

    }

    public function bootingPackage(): void
    {
        parent::bootingPackage();

        Gate::before(function ($user, $ability) {
            if (Permission::isSuperAdmin()) {
                return true;
            }

            return null;
        });

    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        $configFiles = [
            __DIR__.'/../vendor/spatie/laravel-permission/config/permission.php' => 'permission.php',
        ];

        $migrationFiles = [
            //
        ];

        // publish config
        foreach ($configFiles as $filePath => $fileName) {
            $this->publishes([
                $filePath => config_path($fileName),
            ], "{$this->package->shortName()}-config");
        }

        $now = Carbon::now();

        // publish migrations
        foreach ($migrationFiles as $filePath => $fileName) {
            $this->publishes([
                $filePath => $this->generateMigrationName(
                    $fileName,
                    $now
                ), ], "{$this->package->shortName()}-migrations");

            if ($this->package->runsMigrations) {
                $this->loadMigrationsFrom($filePath);
            }
        }

    }
}
