<?php

use JeffersonGoncalves\LaravelShortUrl\GeoIp\MaxMindGeoIpDriver;

it('never breaks when the geoip2 package or database is unavailable', function () {
    config(['short-url.tracking.geoip.maxmind_database_path' => null]);

    $location = (new MaxMindGeoIpDriver)->resolve('203.0.113.42');

    expect($location->country)->toBeNull();
});
