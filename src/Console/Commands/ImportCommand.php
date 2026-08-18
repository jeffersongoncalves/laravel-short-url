<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelShortUrl\Registries\ImporterDriverRegistry;

class ImportCommand extends Command
{
    protected $signature = 'short-url:import {driver : csv|bitly|... (see ImporterDriverRegistry::names())} {source : file path or provider-specific identifier} {--dry-run : Preview only, do not import}';

    protected $description = 'Import short urls from an external source (CSV file or a registered provider driver).';

    public function handle(ImporterDriverRegistry $registry): int
    {
        $driverName = (string) $this->argument('driver');
        $source = (string) $this->argument('source');

        $driver = $registry->driver($driverName);

        if (! $driver) {
            $this->error("Unknown importer driver [{$driverName}]. Available: ".implode(', ', $registry->names()));

            return self::FAILURE;
        }

        $preview = $driver->preview($source);

        foreach ($preview->warnings as $warning) {
            $this->warn($warning);
        }

        $this->info("Found {$preview->totalRows} row(s) to import.");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $report = $driver->import($source);

        $this->info("Imported: {$report->imported}, skipped: {$report->skipped}, failed: {$report->failed}.");

        foreach ($report->errors as $error) {
            $this->error($error);
        }

        return $report->failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
