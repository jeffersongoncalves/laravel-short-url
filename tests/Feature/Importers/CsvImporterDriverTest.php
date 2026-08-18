<?php

use JeffersonGoncalves\LaravelShortUrl\Importers\CsvImporterDriver;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Services\KeyGenerator;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;

function writeCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'short-url-import').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('previews a csv file with columns, sample rows and a total count', function () {
    $path = writeCsv([
        ['destination_url', 'url_key', 'title'],
        ['https://a.example', 'akey', 'A'],
        ['https://b.example', 'bkey', 'B'],
    ]);

    $preview = (new CsvImporterDriver(app(ShortUrlManager::class)))->preview($path);

    expect($preview->totalRows)->toBe(2)
        ->and($preview->columns)->toBe(['destination_url', 'url_key', 'title'])
        ->and($preview->warnings)->toBe([]);
});

it('warns when the destination_url column is missing', function () {
    $path = writeCsv([
        ['title', 'url_key'],
        ['A', 'akey'],
    ]);

    $preview = (new CsvImporterDriver(app(ShortUrlManager::class)))->preview($path);

    expect($preview->warnings)->not->toBe([]);
});

it('imports every valid row and skips rows with no destination_url', function () {
    $path = writeCsv([
        ['destination_url', 'url_key', 'title'],
        ['https://a.example', 'akey01', 'A'],
        ['', 'bkey01', 'B'],
        ['https://c.example', 'ckey01', 'C'],
    ]);

    $report = (new CsvImporterDriver(app(ShortUrlManager::class)))->import($path);

    expect($report->imported)->toBe(2)
        ->and($report->skipped)->toBe(1)
        ->and($report->failed)->toBe(0)
        ->and(ShortUrl::query()->count())->toBe(2);
});

it('reports a failure without stopping the rest of the import', function () {
    $path = writeCsv([
        ['destination_url', 'url_key'],
        ['https://a.example', 'akey02'],
        ['https://b.example', 'bkey02'],
        ['https://c.example', 'ckey02'],
    ]);

    $manager = new class(app(KeyGenerator::class)) extends ShortUrlManager
    {
        public function create(array $attributes): ShortUrl
        {
            if (($attributes['destination_url'] ?? null) === 'https://b.example') {
                throw new RuntimeException('simulated failure');
            }

            return parent::create($attributes);
        }
    };

    $report = (new CsvImporterDriver($manager))->import($path);

    expect($report->imported)->toBe(2)
        ->and($report->failed)->toBe(1)
        ->and($report->errors)->toHaveCount(1)
        ->and(ShortUrl::query()->count())->toBe(2);
});

it('returns an empty preview for a non-existent file', function () {
    $preview = (new CsvImporterDriver(app(ShortUrlManager::class)))->preview('/no/such/file.csv');

    expect($preview->totalRows)->toBe(0);
});
