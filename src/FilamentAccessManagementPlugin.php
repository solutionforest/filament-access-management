<?php

namespace SolutionForest\FilamentAccessManagement;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use SolutionForest\FilamentAccessManagement\Pages\Error as ErrorPage;
use SolutionForest\FilamentAccessManagement\Support\Utils;

/** @phpstan-consistent-constructor */
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
        $middleware = config('filament-access-management.filament.middleware.base', []);
        $middleware = is_array($middleware) ? $middleware : [];
        $middleware = array_values(array_filter($middleware, 'is_string'));

        $panel->middleware($middleware);
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
        return new static;
    }

    public static function get(): self
    {
        $plugin = filament((new static)->getId());

        if (! $plugin instanceof self) {
            throw new Exception('Unable to resolve the '.static::class.' plugin.');
        }

        return $plugin;
    }

    /**
     * @return Closure(NavigationBuilder): NavigationBuilder
     */
    protected function configureNavigation(): Closure
    {
        return function (NavigationBuilder $builder): NavigationBuilder {

            if ($customBuilder = FilamentAuthenticate::getCustomNavigation()) {
                $builder = $customBuilder;
            }

            return $builder->groups(FilamentAuthenticate::getUserNavigationGroups());
        };
    }

    protected function configureComponent(): void
    {
        if (config('filament-access-management.filament.path_permission_checking.action', false)) {
            Action::configureUsing(function (Action $component): void {
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
            });
        }
    }
}
