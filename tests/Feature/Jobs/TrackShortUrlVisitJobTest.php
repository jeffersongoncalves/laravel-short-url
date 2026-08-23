<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\HeadersGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Jobs\TrackShortUrlVisitJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;

/**
 * Regression for GH issue #3: HeadersGeoIpDriver used to read the live
 * request, which doesn't exist by the time this job runs on a real queue
 * worker — it only appeared to work on QUEUE_CONNECTION=sync.
 */
it('resolves cdn geo from the header snapshot taken on the request thread, not the current request', function () {
    config([
        'short-url.tracking.geoip.driver' => 'headers',
        'short-url.tracking.trust_cdn_headers' => true,
    ]);

    $shortUrl = ShortUrl::factory()->create(['track_visits' => true, 'track_ip_address' => true])->fresh();

    // Snapshot taken during the original request (what DispatchTracking
    // stores in the payload).
    $originalRequest = Request::create('/'.$shortUrl->url_key);
    $originalRequest->headers->set('CF-IPCountry', 'BR');
    $originalRequest->headers->set('CF-IPCountry-Name', 'Brazil');

    $payload = [
        'short_url_id' => $shortUrl->id,
        'tenant_id' => $shortUrl->tenant_id,
        'ip' => '203.0.113.42',
        'geo_headers' => HeadersGeoIpDriver::snapshot($originalRequest),
        'user_agent' => 'Mozilla/5.0',
        'referer_url' => null,
        'browser_language' => null,
        'app_host' => 'short.test',
        'is_bot' => false,
        'device_type' => null,
        'operating_system' => null,
        'is_vpn' => false,
        'is_proxy' => false,
        'is_tor' => false,
        'is_datacenter' => false,
        'utm_source' => null,
        'utm_medium' => null,
        'utm_campaign' => null,
        'utm_term' => null,
        'utm_content' => null,
        'selected_variant' => null,
        'matched_rule_index' => null,
        'response_time_ms' => null,
        'track_ip_address' => true,
        'track_browser' => false,
        'track_browser_version' => false,
        'track_operating_system' => false,
        'track_operating_system_version' => false,
        'track_device_type' => false,
        'track_referer_url' => false,
        'track_browser_language' => false,
    ];

    // Simulates the worker process: no CDN headers on "the current
    // request" at all — this is what would have made the old
    // Request-injected driver resolve empty geo.
    app()->instance('request', Request::create('/'));

    (new TrackShortUrlVisitJob($payload))->handle(
        app(VisitRepository::class),
        app(CounterBuffer::class),
        app(GeoIpDriver::class),
    );

    $visit = Visit::query()->where('short_url_id', $shortUrl->id)->first();

    expect($visit)->not->toBeNull()
        ->and($visit->country)->toBe('Brazil')
        ->and($visit->country_code)->toBe('BR');
});
