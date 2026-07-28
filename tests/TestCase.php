<?php

namespace SolutionForest\FilamentAccessManagement\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use SolutionForest\FilamentAccessManagement\FilamentAccessManagementServiceProvider;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\AdminPanelProvider;
use SolutionForest\FilamentAccessManagement\Tests\Fixtures\User;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Testbench does not bind Livewire's DataStore as a shared singleton, which
        // leaves Component::getErrorBag() returning null and crashes every full
        // Filament page render (ViewErrorBag::put() null MessageBag). Bind one
        // shared instance so page-render tests reflect real-app behaviour.
        $this->app->instance(\Livewire\Mechanisms\DataStore::class, new \Livewire\Mechanisms\DataStore);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SolutionForest\\FilamentAccessManagement\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Filament\QueryBuilder\QueryBuilderServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Kirschbaum\PowerJoins\PowerJoinsServiceProvider::class,
            \SolutionForest\FilamentTree\FilamentTreeServiceProvider::class,
            \Guava\IconPicker\IconPickerServiceProvider::class,
            \Spatie\Permission\PermissionServiceProvider::class,
            FilamentAccessManagementServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Auth
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);

        // spatie/laravel-permission — sqlite testing fix
        $app['config']->set('permission.testing', true);

        // Point the package at the test User model
        $app['config']->set('filament-access-management.auth.model', User::class);
    }
}
