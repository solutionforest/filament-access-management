<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use BackedEnum;
use Closure;
use DateInterval;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SolutionForest\FilamentAccessManagement\Models\Menu as MenuModel;
use SolutionForest\FilamentAccessManagement\Navigation\NavigationItem;
use SolutionForest\FilamentTree;

class Menu
{
    /**
     * Get or create a navigation item from db.
     */
    public static function createNavigation(
        string $title,
        ?int $parent = null,
        ?string $icon = null,
        ?string $activeIcon = null,
        ?string $uri = null,
        ?string $badge = null,
        ?string $badgeColor = null,
        bool $isFilamentPanel = false): MenuModel
    {
        return Utils::getMenuModel()::firstOrCreate(
            [
                'title' => $title,
                'parent_id' => $parent ?? -1,
                'is_filament_panel' => $isFilamentPanel,
            ],
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
    public static function getNavigation(string $title, int $parent): ?MenuModel
    {
        return Utils::getMenuModel()::query()
            ->where('title', $title)
            ->where('parent_id', $parent)
            ->first();
    }

    /**
     * Get all navigation items from db.
     *
     * @return Collection<int, array<array-key, mixed>>
     */
    public static function getAllNavigation(): Collection
    {
        $items = Cache::remember(
            static::getCacheKey(),
            static::getCacheExpirationTime(),
            fn (): array => Utils::getMenuModel()::query()->ordered()->get()->toArray()
        );

        $nodes = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $nodes[] = $item;
            }
        }

        return collect($nodes);
    }

    /**
     * Get filament navigation group.
     *
     * @return Collection<array-key, NavigationGroup>
     */
    public static function getNavigationGroups(): Collection
    {
        $model = app(Utils::getMenuModel());

        if (! $model instanceof MenuModel) {
            throw new \UnexpectedValueException('The configured menu model must be a Menu model.');
        }

        $titleColumnName = $model->determineTitleColumnName();
        $iconColumnName = $model->determineIconColumnName();
        $childrenKeyName = FilamentTree\Support\Utils::defaultChildrenKeyName();

        $allNavigationNodes = static::getAllNavigation();

        $tree = $model->toTree($allNavigationNodes);

        $result = [];

        foreach ($tree as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            self::handleTranslatable($item);

            $navGroupLabel = empty($item[$childrenKeyName])
                ? null
                : self::ensureNavigationLabel($item[$titleColumnName] ?? null);

            if (empty($item)) {
                continue;
            }

            $icon = null;
            $childrenNodes = [];

            // Is Navigation Group
            if (! empty($navGroupLabel)) {
                $icon = self::normalizeIcon($item[$iconColumnName] ?? null);
                $children = $item[$childrenKeyName] ?? [];
                $childrenNodes = is_array($children) ? $children : [];
            } else {
                $childrenNodes[] = $item;
            }

            $navigationGroupItems = self::buildNavigationGroupItems($childrenNodes, $navGroupLabel, $icon);

            $result[$navGroupLabel ?? $index] = NavigationGroup::make()
                ->label($navGroupLabel)
                ->icon($icon)
                ->items($navigationGroupItems);
        }

        return collect($result);
    }

    public static function clearCache(): void
    {
        Cache::forget(static::getCacheKey());
    }

    public static function getCacheKey(): string
    {
        $key = config('filament-access-management.cache.navigation.key', 'filament_navigation');

        return is_string($key) && $key !== '' ? $key : 'filament_navigation';
    }

    public static function getCacheExpirationTime(): DateInterval|int
    {
        $expiration = config('filament-access-management.cache.navigation.expiration_time');

        return $expiration instanceof DateInterval || is_int($expiration)
            ? $expiration
            : DateInterval::createFromDateString('24 hours');
    }

    public static function normalizeIcon(mixed $icon): ?string
    {
        if ($icon instanceof BackedEnum) {
            $icon = $icon->value;
        }

        if (! is_string($icon) || $icon === '') {
            return null;
        }

        // Soft handle heroicon value
        if (Heroicon::tryFrom($icon) !== null && ! str($icon)->startsWith('heroicon-')) {
            return 'heroicon-'.$icon;
        }

        return $icon;
    }

    /**
     * @param  array<array-key, mixed>  $final
     */
    private static function handleTranslatable(array &$final): void
    {
        Utils::getMenuModel()::handleTranslatable($final);
    }

    private static function ensureNavigationLabel(mixed $label): ?string
    {
        if (empty($label)) {
            return null;
        }

        if (is_array($label) || $label instanceof Arrayable) {
            $first = collect($label)->first();

            return is_string($first) ? $first : null;
        }

        if (is_string($label)) {
            return $label;
        }

        if (is_scalar($label)) {
            return (string) $label;
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $treeItems
     * @return list<NavigationItem>
     */
    private static function buildNavigationGroupItems(array $treeItems = [], ?string $groupLabel = null, ?string $groupIcon = null): array
    {
        if (empty($treeItems)) {
            return [];
        }

        $model = app(Utils::getMenuModel());

        if (! $model instanceof MenuModel) {
            throw new \UnexpectedValueException('The configured menu model must be a Menu model.');
        }

        $labelColumnName = $model->determineTitleColumnName();
        $iconColumnName = $model->determineIconColumnName();
        $activeIconColumnName = $model->determineActiveIconColumnName();
        $uriColumnName = $model->determineUriColumnName();
        $badgeColumnName = $model->determineBadgeColumnName();
        $badgeColorColumnName = $model->determineBadgeColorColumnName();
        $orderColumnName = $model->determineOrderColumnName();

        $result = [];

        foreach ($treeItems as $treeItem) {
            if (! is_array($treeItem)) {
                continue;
            }

            self::handleTranslatable($treeItem);

            $rawUri = $treeItem[$uriColumnName] ?? '/';
            $rawUri = is_string($rawUri) ? $rawUri : '/';

            $url = trim($rawUri, '/');

            $isFilamentPanel = $treeItem['is_filament_panel'] ?? false;
            $panel = filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel();

            if ($isFilamentPanel == true) {
                $pathInPanel = (string) str($panel->getPath())->trim('/')->append('/');

                if ($panel->hasTenancy()) {
                    $tenantKey = filament()->getTenant()?->getKey();

                    if (is_string($tenantKey) || is_int($tenantKey)) {
                        $pathInPanel .= $tenantKey.'/';
                    }
                }

                $pathInPanel .= $url;

                $url = (string) url($pathInPanel);
            }

            $icon = self::normalizeIcon($treeItem[$iconColumnName] ?? null) ?? Utils::getFilamentDefaultIcon();
            $activeIcon = self::normalizeIcon($treeItem[$activeIconColumnName] ?? null) ?? '';

            $badge = $treeItem[$badgeColumnName] ?? null;
            $badge = is_string($badge) ? $badge : null;

            $badgeColor = $treeItem[$badgeColorColumnName] ?? null;

            if (is_array($badgeColor)) {
                $colors = [];

                foreach ($badgeColor as $colorKey => $colorValue) {
                    if (is_int($colorValue) || is_string($colorValue)) {
                        $colors[$colorKey] = $colorValue;
                    }
                }

                $badgeColor = $colors;
            } elseif (! is_string($badgeColor) && ! $badgeColor instanceof Closure) {
                $badgeColor = null;
            }

            $order = $treeItem[$orderColumnName] ?? 0;
            $sort = is_numeric($order) ? (int) $order : 0;

            $result[] = NavigationItem::make()
                ->label(self::ensureNavigationLabel($treeItem[$labelColumnName] ?? null) ?? '')
                ->group($groupLabel ?? '')
                ->groupIcon($groupIcon ?? '')
                ->icon($icon)
                ->activeIcon($activeIcon)
                ->activeWhen(fn (): bool => request()->is(trim($rawUri, '/')))
                ->sort($sort)
                ->badge($badge, color: $badgeColor)
                ->url($url);
        }

        return $result;
    }
}
