<?php

namespace SolutionForest\FilamentAccessManagement\Database\Seeders;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $helper = FilamentAuthenticate::menu();

        $pages = array_merge([
            Dashboard::class,
        ], Utils::getPages());

        $resources = Utils::getResources();

        $panel = Filament::getCurrentOrDefaultPanel();

        /** @var Collection<array-key, Collection<array-key, NavigationItem>> */
        $navigationGroup = collect(array_merge($pages, $resources))
            ->filter(fn ($item) => is_string($item) && method_exists($item, 'getNavigationItems'))
            ->map(function ($itemFQCN) use ($panel) {
                if (is_subclass_of($itemFQCN, \Filament\Resources\Pages\Page::class) ||
                    is_subclass_of($itemFQCN, Page::class)) {

                    $path = $itemFQCN::getRoutePath($panel);

                } elseif (is_subclass_of($itemFQCN, \Filament\Resources\Resource::class)) {
                    try {

                        $path = (string) str($itemFQCN::getRoutePrefix($panel))->prepend('/')->rtrim('/');

                    } catch (\Exception $e) {
                        return null;
                    }

                } else {
                    return null;
                }

                return NavigationItem::make($itemFQCN::getNavigationLabel())
                    ->group($itemFQCN::getNavigationGroup())
                    ->parentItem($itemFQCN::getNavigationParentItem())
                    ->icon($itemFQCN::getNavigationIcon())
                    ->activeIcon($itemFQCN::getActiveNavigationIcon())
                    // ->isActiveWhen(fn (): bool => request()->routeIs($itemFQCN::getNavigationItemActiveRoutePattern()))
                    ->sort($itemFQCN::getNavigationSort())
                    ->badge($itemFQCN::getNavigationBadge(), color: $itemFQCN::getNavigationBadgeColor())
                    ->badgeTooltip($itemFQCN::getNavigationBadgeTooltip())
                    ->url($path);
                // ->url($itemFQCN::getNavigationUrl());
            })
            ->filter()
            ->groupBy(fn (NavigationItem $navItem) => $navItem->getGroup())
            ->sortKeys();

        foreach ($navigationGroup as $groupName => $collect) {
            $parentId = -1;
            if (! empty($groupName)) {
                $parent = $helper->getNavigation($groupName, -1);
                if (! $parent) {
                    $parent = $helper->createNavigation($groupName);
                }
                $parentId = $parent->id;
            }
            foreach ($collect as $navItem) {
                $helper->createNavigation(
                    title: $navItem->getLabel(),
                    parent: $parentId,
                    icon: $navItem->getIcon(),
                    activeIcon: $navItem->getActiveIcon(),
                    // uri: admin_base_path($navItem->getUrl()),
                    uri: $navItem->getUrl(),
                    badge: $navItem->getBadge(),
                    badgeColor: $navItem->getBadgeColor(),
                    isFilamentPanel: true,
                );
            }
        }
    }
}
