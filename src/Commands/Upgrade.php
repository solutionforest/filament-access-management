<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Composer\InstalledVersions;
use Exception;
use Illuminate\Console\Command;
use SolutionForest\FilamentAccessManagement\Support\Utils;

use function Laravel\Prompts\progress;

class Upgrade extends Command
{
    protected $signature = 'filament-access-management:upgrade';

    public $description = 'Upgrade FilamentAccessManagement';

    private const PACKAGE_NAME = 'solution-forest/filament-access-management';

    public function handle(): int
    {
        if ($this->isLocalDevVersion() || $this->isVersionGreaterThan('1.0.0')) {
            $this->upgradeAfterV1();
        }

        return static::SUCCESS;
    }

    private function upgradeAfterV1()
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

        if (count($v1PathRecords) <= 0) {
            $this->info('No legacy menu uri to upgrade.');

            return;
        }

        $errors = [];
        progress('Updating uri of menu as current version', count($v1PathRecords), function ($index) use ($v1PathRecords, &$errors) {
            foreach ($v1PathRecords as $v1PathRecord) {
                try {
                    $newUri = (string) str($v1PathRecord->uri)
                        ->replace('/admin', '');

                    $v1PathRecord->update([
                        'uri' => $newUri,
                        'is_filament_panel' => true,
                    ]);
                } catch (Exception $e) {
                    $errors[] = "[#{$index}] Updating uri of menu failed (Detail: {$e->getMessage()})";
                }
            }
        });

        if (count($errors) > 0) {
            $this->error('Upgrade completed with errors:');
            foreach ($errors as $error) {
                $this->error($error);
            }
        }
    }

    private function getCurrentVersion(): string
    {
        return preg_replace(
            '/\.x-dev$/',
            '.0',
            InstalledVersions::getPrettyVersion(self::PACKAGE_NAME)
            ?? InstalledVersions::getVersion(self::PACKAGE_NAME)
        );
    }

    private function isVersionGreaterThan(string $version): bool
    {
        return version_compare($this->getCurrentVersion(), $version, '>');
    }

    private function isLocalDevVersion(): bool
    {
        return str_starts_with($this->getCurrentVersion(), 'dev-') || str_ends_with($this->getCurrentVersion(), '.x-dev');
    }
}
