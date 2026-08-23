<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\HeadersGeoIpDriver;

it('reads geolocation from cdn-injected headers when trust_cdn_headers is on', function () {
    config(['short-url.tracking.trust_cdn_headers' => true]);

    $request = Request::create('/');
    $request->headers->set('CF-IPCountry', 'BR');
    $request->headers->set('CF-IPCountry-Name', 'Brazil');

    $location = (new HeadersGeoIpDriver($request))->resolve('203.0.113.42');

    expect($location->countryCode)->toBe('BR')
        ->and($location->country)->toBe('Brazil');
});

it('returns an empty location when no geo headers are present', function () {
    config(['short-url.tracking.trust_cdn_headers' => true]);

    $location = (new HeadersGeoIpDriver(Request::create('/')))->resolve('203.0.113.42');

    expect($location->country)->toBeNull()
        ->and($location->countryCode)->toBeNull();
});

it('ignores cdn headers when trust_cdn_headers is off, even if present', function () {
    config(['short-url.tracking.trust_cdn_headers' => false]);

    $request = Request::create('/');
    $request->headers->set('CF-IPCountry', 'BR');

    $location = (new HeadersGeoIpDriver($request))->resolve('203.0.113.42');

    expect($location->countryCode)->toBeNull();
});

it('resolves geolocation from a header snapshot instead of the live request', function () {
    config(['short-url.tracking.trust_cdn_headers' => true]);

    // Simulates TrackShortUrlVisitJob running in a queue worker: the
    // headers were captured on the request thread and serialized into the
    // job payload — there is no live Request with CDN headers here.
    $headers = HeadersGeoIpDriver::snapshot(tap(Request::create('/'), function (Request $request) {
        $request->headers->set('CF-IPCountry', 'BR');
        $request->headers->set('CF-IPCountry-Name', 'Brazil');
    }));

    $location = (new HeadersGeoIpDriver(Request::create('/')))->resolveFromHeaders($headers);

    expect($location->countryCode)->toBe('BR')
        ->and($location->country)->toBe('Brazil');
});
