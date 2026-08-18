<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Security\IpApiVpnDetectionDriver;

it('flags a proxy/datacenter ip', function () {
    Http::fake(['*ip-api.com*' => Http::response(['status' => 'success', 'proxy' => true, 'hosting' => true])]);

    $result = (new IpApiVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isProxy)->toBeTrue()
        ->and($result->isDatacenter)->toBeTrue();
});

it('returns a clean result for a regular ip', function () {
    Http::fake(['*ip-api.com*' => Http::response(['status' => 'success', 'proxy' => false, 'hosting' => false])]);

    $result = (new IpApiVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isProxy)->toBeFalse()
        ->and($result->isDatacenter)->toBeFalse();
});

it('returns a clean result instead of throwing when the api call fails', function () {
    Http::fake(['*ip-api.com*' => Http::response([], 500)]);

    $result = (new IpApiVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isProxy)->toBeFalse();
});

it('caches the result per ip', function () {
    Http::fake(['*ip-api.com*' => Http::response(['status' => 'success', 'proxy' => true, 'hosting' => false])]);

    $driver = new IpApiVpnDetectionDriver;
    $driver->check('203.0.113.99');
    $driver->check('203.0.113.99');

    Http::assertSentCount(1);
});
