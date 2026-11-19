<?php

namespace SolutionForest\FilamentAccessManagement\Database\Seeders;

use Exception;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Seeder;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use UnitEnum;

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

        if (! $panel instanceof Panel) {
            return;
        }

        $navigationItems = [];

        foreach (array_merge($pages, $resources) as $itemFQCN) {
            if (is_subclass_of($itemFQCN, Page::class)) {
                $path = $itemFQCN::getRoutePath($panel);
            } elseif (is_subclass_of($itemFQCN, Resource::class)) {
                try {
                    $path = (string) str($itemFQCN::getRoutePrefix($panel))->prepend('/')->rtrim('/');
                } catch (Exception) {
                    continue;
                }
            } else {
                continue;
            }

            $navigationItems[] = NavigationItem::make($itemFQCN::getNavigationLabel())
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
        }

        $navigationGroup = collect($navigationItems)
            ->groupBy(fn (NavigationItem $navItem): string|UnitEnum => $navItem->getGroup() ?? '')
            ->sortKeys();

        foreach ($navigationGroup as $groupName => $collect) {
            $groupName = is_string($groupName) ? $groupName : (string) $groupName;

            $parentId = -1;

            if ($groupName !== '') {
                $parent = $helper->getNavigation($groupName, -1);

                if (! $parent) {
                    $parent = $helper->createNavigation($groupName);
                }

                $parentKey = $parent->getKey();
                $parentId = is_int($parentKey) ? $parentKey : (is_string($parentKey) ? (int) $parentKey : -1);
            }

            foreach ($collect as $navItem) {
                $label = $navItem->getLabel();
                $badgeColor = $navItem->getBadgeColor();

                $helper->createNavigation(
                    title: $label instanceof Htmlable ? $label->toHtml() : $label,
                    parent: $parentId,
                    icon: $helper->normalizeIcon($navItem->getIcon()),
                    activeIcon: $helper->normalizeIcon($navItem->getActiveIcon()),
                    // uri: admin_base_path($navItem->getUrl()),
                    uri: $navItem->getUrl(),
                    badge: $navItem->getBadge(),
                    badgeColor: is_string($badgeColor) ? $badgeColor : null,
                    isFilamentPanel: true,
                );
            }
        }
    }
}
