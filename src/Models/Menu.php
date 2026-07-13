<?php

namespace SolutionForest\FilamentAccessManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Support\Utils;
use SolutionForest\FilamentTree\Concern\ModelTree;

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
        if (empty($this->{$uriColumnName})) {
            return null;
        }
        if ($this->is_filament_panel && $panel = (filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel())) {

            $pathInPanel = (string) str($panel->getPath())
                ->trim('/')
                ->append('/')
                ->when($panel->hasTenancy(),
                    fn ($str) => $str
                        ->append(filament()->getTenant()?->getKey())
                        ->append('/'))
                ->append(trim($this->{$uriColumnName}, '/'));

            return url($pathInPanel);
        }

        return $this->{$uriColumnName};
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

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $menu) {
            $iconColumnName = $menu->determineIconColumnName();
            $uriColumnName = $menu->determineUriColumnName();

            $icon = $menu->{$iconColumnName};

            $uri = $menu->{$uriColumnName};

            // Is Navigation Group
            if (count($menu->children) > 0) {
                //
            } else {
                // non-navigation group must have icon
                if (! filled($icon)) {
                    $menu->{$iconColumnName} = Utils::getFilamentDefaultIcon();
                }

                // Ensure uri start with '/' or is valid url
                if (! url()->isValidUrl($uri)) {
                    $menu->{$uriColumnName} = (string) Str::start($uri, '/');
                }
            }
        });

        static::saved(function (self $menu) {
            // Clear cache
            FilamentAuthenticate::menu()->clearCache();
        });

        static::deleted(function (self $menu) {
            // Clear cache
            FilamentAuthenticate::menu()->clearCache();
        });

    }

    public function getTable()
    {
        return Utils::getMenuTableName();
    }
}
