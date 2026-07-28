<?php

namespace SolutionForest\FilamentAccessManagement;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Pages\Error as ErrorPage;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class FilamentAccessManagementPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-access-management-plugin';
    }

    public function register(Panel $panel): void
    {
        $resources = Utils::getResources();

        $pages = array_merge(Utils::getPages(), [
            ErrorPage::class,
        ]);

        $panel->resources($resources);

        $panel->pages($pages);

        // middleware
        $panel->middleware(config('filament-access-management.filament.middleware.base', []));
    }

    public function boot(Panel $panel): void
    {
        if (config('filament-access-management.filament.navigation.enabled', false)) {
            $panel->navigation($this->configureNavigation());
        }

        $this->configureComponent();
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    protected function configureNavigation()
    {
        return function (NavigationBuilder $builder): NavigationBuilder {

            if ($customBuilder = FilamentAuthenticate::getCustomNavigation()) {
                $builder = $customBuilder;
            }

            return $builder->groups(FilamentAuthenticate::getUserNavigationGroups());
        };
    }

    protected function configureComponent()
    {
        if (config('filament-access-management.filament.path_permission_checking.action', false)) {
            Action::configureUsing(function (Action $component) {
                if (method_exists($component, 'getUrl')) {
                    $component->hidden(function () use ($component) {
                        // A record-bound action's URL (e.g. EditAction) requires the
                        // {record} route parameter. When the action is evaluated
                        // without a bound record (table render / header context),
                        // getUrl() throws UrlGenerationException. Treat any
                        // URL-resolution failure as "not URL-permission-gated".
                        try {
                            $url = $component->getUrl();
                        } catch (\Throwable $e) {
                            return false;
                        }

                        if (empty($url)) {
                            return false;
                        }

                        return ! Permission::checkPermission($url);
                    });
                }
            });
        }
    }
}
