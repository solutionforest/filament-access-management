<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentAccessManagement\Concerns\Commands\CanValidateInput;
use SolutionForest\FilamentAccessManagement\Models\Menu;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class MakeMenu extends Command
{
    use CanValidateInput;

    protected $signature = 'make:filament-menu
                            {--title= : The title of the menu}
                            {--icon= : The icon of the menu}
                            {--activeIcon= : The activeIcon of the menu}
                            {--uri= : The uri of the menu}
                            {--badge= : The badge of the menu}
                            {--badgeColor= : The badge color of the menu}
                            {--parent= : The parent ID of the menu}';

    protected $description = 'Create a new menu';

    /** @var array<array-key, mixed> */
    protected array $options;

    public function handle(): int
    {
        $this->options = $this->option();

        $nav = $this->createMenu();

        $this->sendSuccessMessage($nav);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getMenuData(): array
    {
        return [
            'title' => $this->validateInput(fn () => $this->options['title'] ?? $this->ask('Title'), 'title', ['required'], fn () => $this->options['title'] = null),
            'icon' => $this->options['icon'],
            'active_icon' => $this->options['activeIcon'],
            'uri' => $this->options['uri'],
            'badge' => $this->options['badge'],
            'badge_color' => $this->options['badgeColor'],
            'parent_id' => $this->validateInput(fn () => $this->options['parent'] ?? $this->ask('Parent ID'), 'parent', ['integer']),
        ];
    }

    protected function createMenu(): Menu
    {
        return static::getMenuModel()::create($this->getMenuData());
    }

    /**
     * @return class-string<Menu>
     */
    protected function getMenuModel(): string
    {
        return Utils::getMenuModel();
    }

    protected function sendSuccessMessage(Model $model): void
    {
        $title = $model->getAttribute('title');
        $title = is_scalar($title) ? (string) $title : '';

        $this->info('Menu '.$title.' Created !');
    }
}
