<?php

namespace SolutionForest\FilamentAccessManagement;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;

class FilamentAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Filament::serving(function () {
            $this->configureNavigation();
            $this->configureComponent();
        });
    }

    protected function configureNavigation()
    {
        Filament::navigation(function (NavigationBuilder $builder) {
            $groups = FilamentAuthenticate::menu()->getNavigationGroups();

            if (! Permission::isSuperAdmin()) {
                $menu = $groups;

                $checkResult = Permission::checkPermission(
                    collect($menu)
                        ->map(fn (NavigationGroup $item) => $item->getItems())
                        ->flatten()
                        ->map(fn (NavigationItem $navItem) => $navItem->getUrl())
                        ->filter()
                        ->unique()
                        ->toArray()
                );

                if (! is_bool($checkResult)) {
                    $groups = collect();

                    $checkResult = array_keys(array_filter($checkResult));
                    foreach ($menu as $navGroupKey => $navGroup) {
                        if ($navGroup instanceof NavigationGroup) {
                            $newNavGroup = $navGroup;
                            $newNavGroup->items(
                                collect($navGroup->getItems())
                                    ->filter(fn (NavigationItem $navItem) => in_array($navItem->getUrl(), $checkResult))
                                    ->values()
                                    ->toArray()
                            );

                            if (count($newNavGroup->getItems()) > 0) {
                                $groups->put($navGroupKey, $newNavGroup);
                            }
                        }
                    }
                }
            }

            return $builder->groups($groups->toArray());
        });
    }

    protected function configureComponent()
    {
        \Filament\Support\Actions\BaseAction::configureUsing(function (\Filament\Support\Actions\BaseAction $component) {
            if (method_exists($component, 'getUrl')) {
                $component->hidden(function () use ($component) {
                    $url = $component->getUrl();

                    if (empty($url)) {
                        return false;
                    }

                    return ! Permission::checkPermission($url);
                });
            }
        });
    }
}
