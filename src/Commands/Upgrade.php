<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Exception;
use Illuminate\Console\Command;
use SolutionForest\FilamentAccessManagement\Support\Utils;

use function Laravel\Prompts\progress;

class Upgrade extends Command
{
    protected $signature = 'filament-access-management:upgrade';

    public $description = 'Upgrade FilamentAccessManagement';

    public function handle(): int
    {
        $this->upgradeMenuUrl();

        return static::SUCCESS;
    }

    private function upgradeMenuUrl()
    {
        $model = Utils::getMenuModel();

        // Find the old uri on FilamentAccessManagement v1
        $v1PathRecords = $model::where(function ($query) {
            return $query
                // ->orWhere('uri', '/') // Admin default page on filament v2
                ->orWhere('uri', '/admin') // Admin dashboard page on filament v2
                ->orWhere('uri', 'like', '/admin/%'); // The page(s) under admin on filament v2
        })
            ->where('is_filament_panel', false) // default value
            ->get();

        progress('Updating uri of menu as current version', count($v1PathRecords), function () use ($v1PathRecords) {

            foreach ($v1PathRecords as $v1PathRecord) {

                try {

                    $newUri = (string) str($v1PathRecord->uri)
                        ->replace('/admin', '');

                    $v1PathRecord->update([
                        'uri' => $newUri,
                        'is_filament_panel' => true,
                    ]);

                } catch (Exception $e) {
                    $this->error("Updating uri of menu failed (Detail: {$e->getMessage()})");
                }
            }
        });

    }
}
