<?php

namespace SolutionForest\FilamentAccessManagement\Navigation;

use Filament\Navigation\NavigationItem as BaseNavigationItem;

class NavigationItem extends BaseNavigationItem
{
    protected ?string $groupIcon = null;

    public function groupIcon(string $groupIcon): static
    {
        $this->groupIcon = $groupIcon;

        return $this;
    }

    public function getGroupIcon():?string
    {
        return $this->groupIcon;
    }
}
