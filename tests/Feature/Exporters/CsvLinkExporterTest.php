<?php

use JeffersonGoncalves\LaravelShortUrl\Exporters\CsvLinkExporter;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('builds a csv with a header row and one row per short url', function () {
    ShortUrl::factory()->create(['url_key' => 'abc1234', 'destination_url' => 'https://example.com']);

    $csv = (new CsvLinkExporter)->toCsvString();
    $lines = explode("\n", $csv);

    expect($lines[0])->toContain('url_key')
        ->and($lines)->toHaveCount(2)
        ->and($lines[1])->toContain('abc1234');
});

it('produces just the header for no short urls', function () {
    $csv = (new CsvLinkExporter)->toCsvString();

    expect(explode("\n", $csv))->toHaveCount(1);
});
