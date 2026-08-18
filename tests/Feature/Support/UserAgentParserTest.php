<?php

use JeffersonGoncalves\LaravelShortUrl\Support\UserAgentParser;

it('reads device type from the fast path', function () {
    expect(UserAgentParser::fastDeviceType('Mozilla/5.0 (iPad; CPU OS 17_0)'))->toBe('tablet')
        ->and(UserAgentParser::fastDeviceType('Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile'))->toBe('mobile')
        ->and(UserAgentParser::fastDeviceType('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'))->toBe('desktop');
});

it('reads the operating system name from the fast path', function () {
    expect(UserAgentParser::fastOperatingSystem('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'))->toBe('Windows')
        ->and(UserAgentParser::fastOperatingSystem('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'))->toBe('macOS')
        ->and(UserAgentParser::fastOperatingSystem('Mozilla/5.0 (Linux; Android 14)'))->toBe('Android')
        ->and(UserAgentParser::fastOperatingSystem('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'))->toBe('iOS')
        ->and(UserAgentParser::fastOperatingSystem('some unrecognized string'))->toBeNull();
});

it('does a full parse with browser and os versions', function () {
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.129 Safari/537.36';

    expect(UserAgentParser::parse($ua))->toBe([
        'browser' => 'Chrome',
        'browser_version' => '120.0.6099.129',
        'operating_system' => 'Windows',
        'operating_system_version' => '10.0',
    ]);
});

it('parses safari on macOS with dotted version', function () {
    $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15';

    $parsed = UserAgentParser::parse($ua);

    expect($parsed['browser'])->toBe('Safari')
        ->and($parsed['browser_version'])->toBe('17.1')
        ->and($parsed['operating_system_version'])->toBe('10.15.7');
});
