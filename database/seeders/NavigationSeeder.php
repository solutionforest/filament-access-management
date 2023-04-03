<?php

namespace SolutionForest\FilamentAccessManagement\Database\Seeders;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Resources\Resource;
use Illuminate\Database\Seeder;
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

        /** @var \Illuminate\Support\Collection<array-key, \Illuminate\Support\Collection<array-key, \Filament\Navigation\NavigationItem>> */
        $navigationGroup = collect(array_merge($pages, $resources))
            ->filter(fn ($item) => is_string($item) && method_exists($item, 'getNavigationItems'))
            ->map(fn ($item) => $item::getNavigationItems())
            ->flatten()
            ->filter(fn ($navItem) => is_a($navItem, NavigationItem::class))
            ->groupBy(fn (NavigationItem $navItem) => $navItem->getGroup())
            ->sortKeys();

        foreach ($navigationGroup as $groupName => $collect) {
            $parentId = -1;
            if (!empty($groupName)) {
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
                    uri: admin_base_path($navItem->getUrl()),
                    badge: $navItem->getBadge(),
                    badgeColor: $navItem->getBadgeColor(),
                );
            }
        }
    }
}
