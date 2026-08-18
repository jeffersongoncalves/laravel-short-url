<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Security\ProxyCheckVpnDetectionDriver;

it('flags a vpn ip', function () {
    Http::fake(['*proxycheck.io*' => Http::response([
        '203.0.113.42' => ['proxy' => 'yes', 'type' => 'VPN'],
    ])]);

    $result = (new ProxyCheckVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isVpn)->toBeTrue()
        ->and($result->isProxy)->toBeTrue();
});

it('returns a clean result for a non-proxy ip', function () {
    Http::fake(['*proxycheck.io*' => Http::response([
        '203.0.113.42' => ['proxy' => 'no'],
    ])]);

    $result = (new ProxyCheckVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isProxy)->toBeFalse();
});

it('returns a clean result instead of throwing when the api call fails', function () {
    Http::fake(['*proxycheck.io*' => Http::response([], 500)]);

    $result = (new ProxyCheckVpnDetectionDriver)->check('203.0.113.42');

    expect($result->isProxy)->toBeFalse();
});
