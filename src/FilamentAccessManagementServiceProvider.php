<?php

namespace SolutionForest\FilamentAccessManagement;

use Carbon\Carbon;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Config;
use SolutionForest\FilamentAccessManagement\Http\Middleware;
use Spatie\LaravelPackageTools\Package;

class FilamentAccessManagementServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-access-management';

    protected array $widgets = [
        // CustomWidget::class,
    ];

    protected array $styles = [
        // 'filament-access-management' => __DIR__.'/../resources/dist/filament-access-management.css',
    ];

    protected array $scripts = [
        // 'filament-access-management' => __DIR__.'/../resources/dist/filament-access-management.js',
    ];

    // protected array $beforeCoreScripts = [
    //     'plugin-filament-access-management' => __DIR__ . '/../resources/dist/filament-access-management.js',
    // ];

    protected function getResources(): array
    {
        return config( 'filament-access-management.resources', []);
    }

    protected function getPages(): array
    {
        return config('filament-access-management.pages', []);
    }

    public function packageBooted(): void
    {
        $configFiles = [
            __DIR__.'/../vendor/spatie/laravel-permission/config/permission.php' => 'permission.php',
        ];

        $migrationFiles = [
            __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub' => 'create_permission_tables.php',
        ];

        foreach ($configFiles as $filePath => $fileName) {
            $this->publishes([
                $filePath => config_path($fileName),
            ], "{$this->package->shortName()}-config");
        }

        $now = Carbon::now();

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

    public function configurePackage(Package $package): void
    {
        Config::push('app.providers', \Spatie\Permission\PermissionServiceProvider::class);
        Config::push('filament.middleware.base', Middleware\UserRoleMiddleware::class);

        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasTranslations()
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands());
    }

    protected function getMigrations(): array
    {
        return [
            'update_users_table',
        ];
    }

    protected function getCommands(): array
    {
        return [
            Commands\InstallCommand::class,
            Commands\MakeAdminUserCommand::class,
        ];
    }
}
