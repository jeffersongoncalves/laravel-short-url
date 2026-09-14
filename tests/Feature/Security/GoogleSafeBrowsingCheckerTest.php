<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\LaravelShortUrl\Security\GoogleSafeBrowsingChecker;

it('returns unknown when no api key is configured', function () {
    config(['short-url.security.safe_browsing.api_key' => null]);

    $result = (new GoogleSafeBrowsingChecker)->check('https://example.com');

    expect($result->status)->toBe('unknown');
});

it('returns safe when the api reports no matches', function () {
    config(['short-url.security.safe_browsing.api_key' => 'test-key']);
    Http::fake(['*safebrowsing*' => Http::response(['matches' => []], 200)]);

    $result = (new GoogleSafeBrowsingChecker)->check('https://example.com');

    expect($result->status)->toBe('safe');
});

it('returns unsafe with threat types when the api reports matches', function () {
    config(['short-url.security.safe_browsing.api_key' => 'test-key']);
    Http::fake(['*safebrowsing*' => Http::response([
        'matches' => [['threatType' => 'MALWARE']],
    ], 200)]);

    $result = (new GoogleSafeBrowsingChecker)->check('https://evil.example');

    expect($result->status)->toBe('unsafe')
        ->and($result->threats)->toBe(['MALWARE']);
});

it('returns unknown instead of throwing when the api call fails', function () {
    config(['short-url.security.safe_browsing.api_key' => 'test-key']);
    Http::fake(['*safebrowsing*' => Http::response([], 500)]);

    $result = (new GoogleSafeBrowsingChecker)->check('https://example.com');

    expect($result->status)->toBe('unknown');
});

it('logs instead of reporting a connection failure, falling back to unknown', function () {
    config(['short-url.security.safe_browsing.api_key' => 'test-key']);
    Http::fake(function () {
        throw new ConnectionException('Resolving timed out after 3000 milliseconds');
    });
    Log::spy();

    $result = (new GoogleSafeBrowsingChecker)->check('https://example.com');

    expect($result->status)->toBe('unknown');
    Log::shouldHaveReceived('info')->once();
});
