<?php

namespace SolutionForest\FilamentAccessManagement\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentTree\Concern\ModelTree;

/**
 * @property int $id
 * @property string $title
 * @property string|null $icon
 * @property string|null $active_icon
 * @property string|null $uri
 * @property bool $is_filament_panel
 * @property string|null $badge
 * @property string|null $badge_color
 * @property int $parent_id
 * @property int $order
 * @property Collection<int, Menu> $children
 */
class Menu extends Model
{
    use ModelTree;

    public $fillable = [
        'title',
        'icon',
        'active_icon',
        'uri',
        'is_filament_panel',
        'badge',
        'badge_color',
        'parent_id',
        'order',
    ];

    public $casts = [
        'is_filament_panel' => 'boolean',
    ];

    public function getNavigationUrl(): ?string
    {
        $uriColumnName = $this->determineUriColumnName();
        $uri = $this->getAttribute($uriColumnName);

        if (! is_string($uri) || $uri === '') {
            return null;
        }

        $isFilamentPanel = $this->getAttribute('is_filament_panel');
        $panel = filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel();

        if ($isFilamentPanel) {
            $pathInPanel = (string) str($panel->getPath())->trim('/')->append('/');

            if ($panel->hasTenancy()) {
                $tenantKey = filament()->getTenant()?->getKey();

                if (is_string($tenantKey) || is_int($tenantKey)) {
                    $pathInPanel .= $tenantKey.'/';
                }
            }

            $pathInPanel .= trim($uri, '/');

            return (string) url($pathInPanel);
        }

        return $uri;
    }

    public function determineTitleColumnName(): string
    {
        return 'title';
    }

    public function determineIconColumnName(): string
    {
        return 'icon';
    }

    public function determineActiveIconColumnName(): string
    {
        return 'active_icon';
    }

    public function determineUriColumnName(): string
    {
        return 'uri';
    }

    public function determineBadgeColumnName(): string
    {
        return 'badge';
    }

    public function determineBadgeColorColumnName(): string
    {
        return 'badge_color';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $menu): void {
            $iconColumnName = $menu->determineIconColumnName();
            $uriColumnName = $menu->determineUriColumnName();

            $icon = $menu->getAttribute($iconColumnName);
            $uri = $menu->getAttribute($uriColumnName);

            // Is Navigation Group
            if (count($menu->children) > 0) {
                //
            } else {
                // non-navigation group must have icon
                if (! filled($icon)) {
                    $menu->setAttribute($iconColumnName, Utils::getFilamentDefaultIcon());
                }

                // Ensure uri start with '/' or is valid url
                $uriValue = is_string($uri) ? $uri : '';

                if (! url()->isValidUrl($uriValue)) {
                    $menu->setAttribute($uriColumnName, (string) Str::start($uriValue, '/'));
                }
            }
        });

        static::saved(function (self $menu): void {
            // Clear cache
            FilamentAuthenticate::menu()->clearCache();
        });

        static::deleted(function (self $menu): void {
            // Clear cache
            FilamentAuthenticate::menu()->clearCache();
        });

    }

    public function getTable(): string
    {
        return Utils::getMenuTableName() ?? parent::getTable();
    }
}
