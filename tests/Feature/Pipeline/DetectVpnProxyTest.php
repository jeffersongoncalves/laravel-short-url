<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VpnDetectionDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\ThreatResult;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\DetectVpnProxy;
use Symfony\Component\HttpKernel\Exception\HttpException;

function bindFakeVpnDriver(ThreatResult $result): void
{
    app()->bind(VpnDetectionDriver::class, fn () => new class($result) implements VpnDetectionDriver
    {
        public function __construct(protected ThreatResult $result) {}

        public function check(string $ip): ThreatResult
        {
            return $this->result;
        }
    });
}

it('skips detection entirely when mode is off', function () {
    config(['short-url.security.vpn_detection.mode' => 'off']);
    bindFakeVpnDriver(new ThreatResult(isVpn: true));

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');
    $result = (new DetectVpnProxy)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking)->not->toHaveKey('is_vpn');
});

it('flags without blocking in flag mode', function () {
    config(['short-url.security.vpn_detection.mode' => 'flag']);
    bindFakeVpnDriver(new ThreatResult(isVpn: true));

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');
    $result = (new DetectVpnProxy)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_vpn'])->toBeTrue();
});

it('blocks with a 403 in block mode when a vpn is detected', function () {
    config(['short-url.security.vpn_detection.mode' => 'block']);
    bindFakeVpnDriver(new ThreatResult(isVpn: true));

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');

    (new DetectVpnProxy)($context, fn (RedirectContext $c) => $c);
})->throws(HttpException::class);

it('does not block a clean ip in block mode', function () {
    config(['short-url.security.vpn_detection.mode' => 'block']);
    bindFakeVpnDriver(new ThreatResult);

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');
    $result = (new DetectVpnProxy)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_vpn'])->toBeFalse();
});
