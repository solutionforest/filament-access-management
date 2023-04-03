<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SolutionForest\FilamentAccessManagement\Database\Seeders;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\FilamentAccessManagementServiceProvider;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class InstallCommand extends Command
{
    protected $signature = 'filament-access-management:install';

    public $description = 'Install Filament Access Management';

    public function handle(): int
    {
        // // Config files
        // if (! ($this->configExists('permission.php') && $this->configExists('filament-access-management.php'))) {
        //     $this->publishConfiguration();
        //     $this->info('Published configuration');
        // } else {
        //     if ($this->shouldOverwriteConfig()) {
        //         $this->info('Overwriting configuration file...');
        //         $this->publishConfiguration($force = true);
        //     } else {
        //         $this->info('Existing configuration was not overwritten');
        //     }
        // }

        // Migration files
        $this->publishMigrations();

        // Create tables and its records
        $this->initDatabase();

        $this->info('Installed');

        return static::SUCCESS;
    }

    private function configExists($fileName)
    {
        return File::exists(config_path($fileName));
    }

    private function shouldOverwriteConfig()
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => FilamentAccessManagementServiceProvider::class,
            '--tag' => 'filament-access-management-config',
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    private function publishMigrations()
    {
        $params = [
            '--provider' => FilamentAccessManagementServiceProvider::class,
            '--tag' => 'filament-access-management-migrations',
        ];

        $this->call('vendor:publish', $params);
    }

    public function initDatabase()
    {
        $this->call('migrate');

        $classes = [
            Seeders\UserPermissionSeeder::class,
            Seeders\NavigationSeeder::class,
        ];
        foreach ($classes as $class) {
            $params = [
                '--class' => $class,
            ];

            $this->call('db:seed', $params);
        }

        // Clear cache
        FilamentAuthenticate::clearPermissionCache();
        FilamentAuthenticate::menu()->clearCache();
    }
}
