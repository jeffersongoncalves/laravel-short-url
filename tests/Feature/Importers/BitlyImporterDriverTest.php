<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Importers\BitlyImporterDriver;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;

it('warns and returns an empty preview when no access token is configured', function () {
    config(['short-url.importers.bitly.access_token' => null]);

    $preview = (new BitlyImporterDriver(app(ShortUrlManager::class)))->preview('group123');

    expect($preview->totalRows)->toBe(0)
        ->and($preview->warnings)->not->toBe([]);
});

it('previews the first page of bitlinks', function () {
    config(['short-url.importers.bitly.access_token' => 'test-token']);
    Http::fake(['*api-ssl.bitly.com*' => Http::response([
        'links' => [
            ['id' => 'bit.ly/abc', 'long_url' => 'https://a.example', 'title' => 'A'],
        ],
        'pagination' => ['total' => 1, 'next' => null],
    ])]);

    $preview = (new BitlyImporterDriver(app(ShortUrlManager::class)))->preview('group123');

    expect($preview->totalRows)->toBe(1)
        ->and($preview->sampleRows)->toHaveCount(1);
});

it('imports every link across paginated results', function () {
    config(['short-url.importers.bitly.access_token' => 'test-token']);

    Http::fakeSequence()
        ->push([
            'links' => [['id' => '1', 'long_url' => 'https://a.example']],
            'pagination' => ['total' => 2, 'next' => 'has-more'],
        ])
        ->push([
            'links' => [['id' => '2', 'long_url' => 'https://b.example']],
            'pagination' => ['total' => 2, 'next' => null],
        ]);

    $report = (new BitlyImporterDriver(app(ShortUrlManager::class)))->import('group123');

    expect($report->imported)->toBe(2)
        ->and(ShortUrl::query()->count())->toBe(2);
});

it('skips a link with no long_url', function () {
    config(['short-url.importers.bitly.access_token' => 'test-token']);
    Http::fake(['*api-ssl.bitly.com*' => Http::response([
        'links' => [['id' => '1']],
        'pagination' => ['total' => 1, 'next' => null],
    ])]);

    $report = (new BitlyImporterDriver(app(ShortUrlManager::class)))->import('group123');

    expect($report->skipped)->toBe(1)
        ->and($report->imported)->toBe(0);
});
