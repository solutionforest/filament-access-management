<?php

namespace SolutionForest\FilamentAccessManagement;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Http\Middleware;
use SolutionForest\FilamentAccessManagement\Http\Auth\Permission;
use Spatie\LaravelPackageTools\Package;

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
        if (! Permission::isSuperAdmin()) {

            $filamentNavigation = Filament::getNavigation();
            Filament::navigation(function (NavigationBuilder $builder) use ($filamentNavigation): NavigationBuilder {

                $groups = $filamentNavigation;

                $checkResult = Permission::checkPermission(
                    collect($filamentNavigation)
                        ->map(fn (NavigationGroup $item) => $item->getItems())
                        ->flatten()
                        ->map(fn (NavigationItem $navItem) => $navItem->getUrl())
                        ->filter()
                        ->unique()
                        ->toArray()
                );

                if (! is_bool($checkResult)) {
                    $groups = [];

                    $checkResult = array_keys(array_filter($checkResult));
                    foreach ($filamentNavigation as $navGroupKey => $navGroup) {

                        if ($navGroup instanceof NavigationGroup) {

                            $newNavGroup = $navGroup;
                            $newNavGroup->items(
                                collect($navGroup->getItems())
                                    ->filter(fn (NavigationItem $navItem) => in_array($navItem->getUrl(), $checkResult))
                                    ->values()
                                    ->toArray()
                            );

                            if (count($newNavGroup->getItems()) > 0) {

                                $groups[$navGroupKey] = $newNavGroup;
                            }
                        }
                    }
                }

                return $builder->groups($groups);
            });
        }
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
