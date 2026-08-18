<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\DetectBot;

it('flags a bot user agent and fills the fast-path fields', function () {
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)');

    $context = new RedirectContext($request, 'abc1234');

    $result = (new DetectBot)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_bot'])->toBeTrue()
        ->and($result->tracking['device_type'])->toBe('desktop');
});

it('does not flag a regular browser and reads device/os', function () {
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile Chrome/120.0.0.0');

    $context = new RedirectContext($request, 'abc1234');

    $result = (new DetectBot)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_bot'])->toBeFalse()
        ->and($result->tracking['device_type'])->toBe('mobile')
        ->and($result->tracking['operating_system'])->toBe('Android');
});

it('flags a qr scan from the source query param', function () {
    $context = new RedirectContext(Request::create('/?source=qr'), 'abc1234');

    $result = (new DetectBot)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_qr_scan'])->toBeTrue();
});

it('does not flag a regular visit as a qr scan', function () {
    $context = new RedirectContext(Request::create('/'), 'abc1234');

    $result = (new DetectBot)($context, fn (RedirectContext $c) => $c);

    expect($result->tracking['is_qr_scan'])->toBeFalse();
});
