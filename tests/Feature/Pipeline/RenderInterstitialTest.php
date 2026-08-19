<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\Pixel;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\RenderInterstitial;

function interstitialContext(ShortUrl $shortUrl, array $tracking = []): RedirectContext
{
    $context = new RedirectContext(Request::create('/'.$shortUrl->url_key), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;
    $context->finalUrl = $shortUrl->destination_url;
    $context->tracking = $tracking;

    return $context;
}

it('passes through when there is nothing to render', function () {
    $shortUrl = ShortUrl::factory()->create();

    $result = (new RenderInterstitial)(interstitialContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});

it('renders attached pixel scripts', function () {
    $shortUrl = ShortUrl::factory()->create();
    $pixel = Pixel::factory()->create(['provider_key' => 'meta_pixel', 'config' => ['pixel_id' => '999']]);
    $shortUrl->pixels()->attach($pixel->id);

    $response = (new RenderInterstitial)(interstitialContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('999');
});

it('shows a consent banner instead of auto-firing pixels when required', function () {
    config(['short-url.pixels.require_consent' => true]);
    $shortUrl = ShortUrl::factory()->create();
    $pixel = Pixel::factory()->create();
    $shortUrl->pixels()->attach($pixel->id);

    $response = (new RenderInterstitial)(interstitialContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($response->getContent())->toContain('id="consent"');
});
