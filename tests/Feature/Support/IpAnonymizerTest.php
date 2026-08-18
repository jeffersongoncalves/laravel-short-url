<?php

use JeffersonGoncalves\LaravelShortUrl\Support\IpAnonymizer;

it('truncates an ipv4 address to its /24', function () {
    expect(IpAnonymizer::truncate('203.0.113.42'))->toBe('203.0.113.0');
});

it('truncates an ipv6 address to its /48', function () {
    expect(IpAnonymizer::truncate('2001:db8:85a3:0:0:8a2e:370:7334'))->toBe('2001:0db8:85a3::');
});

it('detects the ip version', function () {
    expect(IpAnonymizer::version('203.0.113.42'))->toBe(4)
        ->and(IpAnonymizer::version('2001:db8::1'))->toBe(6);
});

it('produces a stable salted hash for the same ip', function () {
    config(['short-url.tracking.ip_hash_salt' => 'fixed-salt']);

    expect(IpAnonymizer::hash('203.0.113.42'))->toBe(IpAnonymizer::hash('203.0.113.42'));
});

it('produces a different hash when the salt changes', function () {
    config(['short-url.tracking.ip_hash_salt' => 'salt-a']);
    $first = IpAnonymizer::hash('203.0.113.42');

    config(['short-url.tracking.ip_hash_salt' => 'salt-b']);
    $second = IpAnonymizer::hash('203.0.113.42');

    expect($first)->not->toBe($second);
});
