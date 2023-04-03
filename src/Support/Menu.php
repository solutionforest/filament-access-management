<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SolutionForest\FilamentAccessManagement\Navigation\NavigationItem;
use SolutionForest\FilamentTree;

class Menu
{
    /**
     * Get or create a navigation item from db.
     */
    public static function createNavigation(string $title,
        ?int $parent = null,
        ?string $icon= null,
        ?string $activeIcon= null,
        ?string $uri= null,
        ?string $badge= null,
        ?string $badgeColor= null): Model
    {
        return Utils::getMenuModel()::firstOrCreate(
            ['title' => $title, 'parent_id' => $parent ?? -1],
            [
                'icon' => $icon,
                'active_icon' => $activeIcon,
                'uri' => $uri,
                'badge' => $badge,
                'badge_color' => $badgeColor,
            ]
        );
    }

    /**
     * Get a navigation item from db.
     */
    public static function getNavigation(string $title, int $parent): ?Model
    {
        return Utils::getMenuModel()::query()
            ->where('title', $title)
            ->where('parent_id', $parent)
            ->first();
    }

    /**
     * Get all navigation items from db.
     */
    public static function getAllNavigation(): Collection
    {
        return Cache::remember(
            static::getCacheKey(),
            static::getCacheExpirationTime(),
            fn () => collect(Utils::getMenuModel()::ordered()->get())
        );
    }

    /**
     * Get filament navigation group.
     *
     * @return Collection<string,NavigationGroup>
     */
    public static function getNavigationGroups()
    {
        $model = app(Utils::getMenuModel());
        $nodes = static::getAllNavigation();

        $titleColumnName = method_exists($model, 'determineTitleColumnName') ? $model->determineTitleColumnName() : 'title';
        $childrenKeyName = FilamentTree\Support\Utils::defaultChildrenKeyName();

        $tree = [];

        if (method_exists($model, 'toTree')) {
            $tree = $model->toTree($nodes);
        } else {
            $tree = FilamentTree\Support\Utils::buildNestedArray(
                nodes: static::getAllNavigation(),
                parentId: null,
                primaryKeyName: method_exists($model, 'getKeyName') ? $model->getKeyName() : null,
                parentKeyName: method_exists($model, 'determineParentColumnName')? $model->determineParentColumnName() : null,
                childrenKeyName: $childrenKeyName,
            );
        }

        return collect($tree)
            ->groupBy(fn ($item) => empty($item[$childrenKeyName]) ? "" : $item[$titleColumnName])
            ->map(function ($items, $label) use ($model, $childrenKeyName) {
                $nodes = collect($items)->toArray();

                if (empty($nodes)) {
                    return null;
                }

                $icon = null;
                // Is Navigation Group
                if (! empty($label)) {
                    $iconColumnName = method_exists($model, 'determineIconColumnName') ? $model->determineIconColumnName() : 'icon';
                    $icon = collect($nodes)->map(fn ($item) => $item[$iconColumnName] ?? null)->filter()->first();

                    $nodes = collect($nodes)->map(fn ($item) => $item[$childrenKeyName])->filter()->first() ?? [];
                }
                $navigationGroupItems = static::buildNavigationGroupItems($childrenKeyName, $nodes, $label, $icon);

                return NavigationGroup::make()
                    ->label(empty($label)? null : $label)
                    ->icon($icon)
                    ->items($navigationGroupItems);
            })
            ->filter()
            ->sortKeys();
    }

    public static function clearCache(): void
    {
        Cache::forget(static::getCacheKey());
    }

    public static function getCacheKey(): string
    {
        return config('filament-access-management.cache.navigation.key', 'filament_navigation');
    }


    public static function getCacheExpirationTime(): \DateInterval|int
    {
        return config('filament-access-management.cache.navigation.expiration_time') ?: \DateInterval::createFromDateString('24 hours');
    }

    private static function buildNavigationGroupItems(string $childrenKeyName, array $treeItems = [], ?string $groupLabel = null, ?string $groupIcon = null): array
    {
        if (empty($treeItems)) {
            return [];
        }

        $model = app(Utils::getMenuModel());

        $labelColumnName = method_exists($model, 'determineTitleColumnName') ? $model->determineTitleColumnName() : 'title';
        $iconColumnName = method_exists($model, 'determineIconColumnName') ? $model->determineIconColumnName() : 'icon';
        $activeIconColumnName = method_exists($model, 'determineActiveIconColumnName') ? $model->determineActiveIconColumnName() : 'active_icon';
        $uriColumnName = method_exists($model, 'determineUriColumnName') ? $model->determineUriColumnName() : 'uri';
        $badgeColumnName = method_exists($model, 'determineBadgeColumnName') ? $model->determineBadgeColumnName() : 'badge';
        $badgeColorColumnName = method_exists($model, 'determineBadgeColorColumnName') ? $model->determineBadgeColorColumnName() : 'badge_color';
        $orderColumnName = method_exists($model, 'determineOrderColumnName') ? $model->determineOrderColumnName() : FilamentTree\Support\Utils::orderColumnName();

        return collect($treeItems)
            ->map(fn (array $treeItem) =>
                NavigationItem::make($treeItem[$labelColumnName])
                    ->group($groupLabel ?? "")
                    ->groupIcon($groupIcon ?? "")
                    ->icon($treeItem[$iconColumnName] ?? "")
                    ->activeIcon($treeItem[$activeIconColumnName] ?? "")
                    ->isActiveWhen(fn (): bool => request()->is(trim(($treeItem[$uriColumnName] ?? "/"), '/')))
                    ->sort(intval($treeItem[$orderColumnName] ?? 0))
                    ->badge(($treeItem[$badgeColumnName] ?? null), color: ($treeItem[$badgeColorColumnName] ?? null))
                    ->url(admin_url(trim(($treeItem[$uriColumnName] ?? "/"), '/')))
            )->toArray();
    }
}
