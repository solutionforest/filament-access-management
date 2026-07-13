<?php

namespace SolutionForest\FilamentAccessManagement;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use SolutionForest\FilamentAccessManagement\Commands\MakeMenu;
use SolutionForest\FilamentAccessManagement\Commands\MakeSuperAdminUser;
use SolutionForest\FilamentAccessManagement\Commands\Upgrade;
use SolutionForest\FilamentAccessManagement\Database\Seeders\NavigationSeeder;
use SolutionForest\FilamentAccessManagement\Database\Seeders\UserPermissionSeeder;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
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
                            UserPermissionSeeder::class,
                            NavigationSeeder::class,
                        ];
                        foreach ($classes as $class) {
                            $params = [
                                '--class' => $class,
                            ];

                            $command->call('db:seed', $params);
                        }
                        // Clear cache
                        FilamentAuthenticate::clearPermissionCache();
                        FilamentAuthenticate::menu()->clearCache();
                    });
            });
    }

    protected function getCommands(): array
    {
        return [
            MakeSuperAdminUser::class,
            MakeMenu::class,
            Upgrade::class,
        ];
    }

    protected function getMigrations(): array
    {
        return [
            'create_filament_admin_tables',
            'upgrade_menu_table',
        ];
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->scoped('filament-access-management', function (): FilamentAccessManagement {
            return app(FilamentAccessManagement::class);
        });

        // Config::push('app.providers', \Spatie\Permission\PermissionServiceProvider::class);

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

        if ($this->app->runningInConsole()) {

            $configFiles = [
                __DIR__.'/../vendor/spatie/laravel-permission/config/permission.php' => 'permission.php',
            ];

            // publish config
            foreach ($configFiles as $filePath => $fileName) {
                $this->publishes([
                    $filePath => config_path($fileName),
                ], "{$this->package->shortName()}-config");
            }
        }

    }
}
