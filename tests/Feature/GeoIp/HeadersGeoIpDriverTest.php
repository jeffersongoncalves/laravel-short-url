<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\HeadersGeoIpDriver;

it('reads geolocation from cdn-injected headers', function () {
    $request = Request::create('/');
    $request->headers->set('CF-IPCountry', 'BR');
    $request->headers->set('CF-IPCountry-Name', 'Brazil');

    $location = (new HeadersGeoIpDriver($request))->resolve('203.0.113.42');

    expect($location->countryCode)->toBe('BR')
        ->and($location->country)->toBe('Brazil');
});

it('returns an empty location when no geo headers are present', function () {
    $location = (new HeadersGeoIpDriver(Request::create('/')))->resolve('203.0.113.42');

    expect($location->country)->toBeNull()
        ->and($location->countryCode)->toBeNull();
});
