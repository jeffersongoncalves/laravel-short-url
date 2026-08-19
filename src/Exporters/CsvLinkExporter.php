<?php

namespace JeffersonGoncalves\LaravelShortUrl\Exporters;

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * Builds a CSV of every short url. No new dependency — Excel/PDF export
 * can be layered on top the same way (maatwebsite/excel,
 * barryvdh/laravel-dompdf) without this package requiring either.
 */
class CsvLinkExporter
{
    /**
     * @var array<int, string>
     */
    protected const COLUMNS = [
        'uuid', 'url_key', 'destination_url', 'title', 'is_enabled',
        'total_visits', 'unique_visits', 'created_at',
    ];

    public function toCsvString(): string
    {
        $rows = [implode(',', self::COLUMNS)];

        foreach (ShortUrl::query()->orderBy('id')->cursor() as $shortUrl) {
            $line = fopen('php://memory', 'w');
            fputcsv($line, array_map(fn (string $column) => (string) data_get($shortUrl, $column), self::COLUMNS));
            rewind($line);
            $rows[] = rtrim((string) stream_get_contents($line));
            fclose($line);
        }

        return implode("\n", $rows);
    }
}
