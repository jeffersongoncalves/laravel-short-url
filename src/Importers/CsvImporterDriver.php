<?php

namespace JeffersonGoncalves\LaravelShortUrl\Importers;

use JeffersonGoncalves\LaravelShortUrl\Contracts\ImporterDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\ImportPreview;
use JeffersonGoncalves\LaravelShortUrl\Data\ImportReport;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;
use SplFileObject;
use Throwable;

/**
 * CSV import. $source is a file path. Expected columns: destination_url
 * (required), url_key, title — any other column is ignored. No new
 * dependency: SplFileObject's built-in CSV mode is enough for this shape.
 */
class CsvImporterDriver implements ImporterDriver
{
    public function __construct(protected ShortUrlManager $manager) {}

    public function preview(string $source): ImportPreview
    {
        $rows = $this->readRows($source);
        $columns = $rows === [] ? [] : array_keys($rows[0]);
        $warnings = in_array('destination_url', $columns, true) ? [] : ['Missing required column: destination_url'];

        return new ImportPreview(
            totalRows: count($rows),
            sampleRows: array_slice($rows, 0, 5),
            columns: $columns,
            warnings: $warnings,
        );
    }

    public function import(string $source): ImportReport
    {
        $rows = $this->readRows($source);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $destinationUrl = trim((string) ($row['destination_url'] ?? ''));

            if ($destinationUrl === '') {
                $skipped++;

                continue;
            }

            try {
                $this->manager->create(array_filter([
                    'destination_url' => $destinationUrl,
                    'url_key' => filled($row['url_key'] ?? null) ? $row['url_key'] : null,
                    'title' => filled($row['title'] ?? null) ? $row['title'] : null,
                ]));
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Row {$index}: {$e->getMessage()}";
            }
        }

        return new ImportReport($imported, $skipped, $failed, $errors);
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function readRows(string $source): array
    {
        if (! is_file($source)) {
            return [];
        }

        $file = new SplFileObject($source);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $header = null;
        $rows = [];

        foreach ($file as $line) {
            if ($line === [null] || $line === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($column) => trim((string) $column), $line);

                continue;
            }

            $rows[] = array_combine($header, array_pad($line, count($header), null));
        }

        return $rows;
    }
}
