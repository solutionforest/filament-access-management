<?php

namespace SolutionForest\FilamentAccessManagement\Commands;

use Composer\InstalledVersions;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
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

        return self::SUCCESS;
    }

    private function upgradeAfterV1(): void
    {
        $model = Utils::getMenuModel();

        // Find the old uri on FilamentAccessManagement v1
        $v1PathRecords = $model::query()
            ->where(function (Builder $query): Builder {
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
        progress('Updating uri of menu as current version', count($v1PathRecords), function (int $index) use ($v1PathRecords, &$errors): void {
            foreach ($v1PathRecords as $v1PathRecord) {
                try {
                    $uri = $v1PathRecord->getAttribute('uri');
                    $newUri = (string) str(is_string($uri) ? $uri : '')
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
        $version = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME)
            ?? InstalledVersions::getVersion(self::PACKAGE_NAME)
            ?? '0.0.0';

        $result = preg_replace(
            '/\.x-dev$/',
            '.0',
            $version
        );

        return is_string($result) ? $result : $version;
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
