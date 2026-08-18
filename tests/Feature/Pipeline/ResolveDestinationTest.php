<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveDestination;

it('leaves the destination untouched for a single destination type', function () {
    $shortUrl = ShortUrl::factory()->create(['destination_type' => 'single', 'destination_url' => 'https://example.com']);
    $context = new RedirectContext(Request::create('/'), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    (new ResolveDestination)($context, fn (RedirectContext $c) => $c);

    expect($context->destinationUrl)->toBeNull();
});

it('resolves a split destination and records the selected variant', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'split',
        'destination_url' => 'https://example.com/base',
        'rotation_variants' => [['url' => 'https://a.test', 'weight' => 100, 'label' => 'A']],
    ]);
    $context = new RedirectContext(Request::create('/'), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    (new ResolveDestination)($context, fn (RedirectContext $c) => $c);

    expect($context->destinationUrl)->toBe('https://a.test')
        ->and($context->tracking['selected_variant'])->toBe('A');
});
