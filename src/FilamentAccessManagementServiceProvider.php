<?php

namespace SolutionForest\FilamentAccessManagement;

use Carbon\Carbon;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use Spatie\LaravelPackageTools\Package;

class FilamentAccessManagementServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-access-management';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands());
    }

    protected function getCommands(): array
    {
        return [
            Commands\InstallCommand::class,
            Commands\MakeSuperAdminUser::class,
        ];
    }

    protected function getMigrations(): array
    {
        return [
            'create_filament_admin_tables',
        ];
    }

    protected function getResources(): array
    {
        return array_values(config('filament-access-management.resources', []));
    }

    protected function getPages(): array
    {
        return array_merge([
            Pages\Error::class,
        ]);
    }

    public function packageRegistered(): void
    {
        $this->app->scoped('filament-access-management', function (): FilamentAccessManagement {
            return app(FilamentAccessManagement::class);
        });

        Config::push('app.providers', \Spatie\Permission\PermissionServiceProvider::class);

        // middleware
        foreach (config('filament-access-management.filament.middleware.base', []) as $middleware) {
            Config::push('filament.middleware.base', $middleware);
        }
        parent::packageRegistered();
    }

    public function bootingPackage(): void
    {
        Gate::before(function ($user, $ability) {
            if (Permission::isSuperAdmin()) {
                return true;
            }

            return null;
        });

        parent::bootingPackage();
    }

    public function packageBooted(): void
    {
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

        parent::packageBooted();
    }
}
