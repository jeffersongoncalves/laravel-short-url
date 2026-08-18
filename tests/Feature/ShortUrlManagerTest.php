<?php

use JeffersonGoncalves\LaravelShortUrl\Exceptions\RequiredUtmParameterMissing;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;

afterEach(function () {
    config(['short-url.utm.required' => []]);
});

it('creates a link when no utm parameter is required', function () {
    $shortUrl = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']);

    expect($shortUrl->id)->not->toBeNull();
});

it('rejects creation when a required utm parameter is missing', function () {
    config(['short-url.utm.required' => ['utm_medium']]);

    expect(fn () => app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']))
        ->toThrow(RequiredUtmParameterMissing::class);
});

it('accepts creation once the required utm parameter is supplied directly', function () {
    config(['short-url.utm.required' => ['utm_medium']]);

    $shortUrl = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://example.com',
        'utm_medium' => 'sms',
    ]);

    expect($shortUrl->utm_medium)->toBe('sms');
});

it('satisfies a required utm parameter supplied via utm_template_id', function () {
    config(['short-url.utm.required' => ['utm_medium']]);
    $template = UtmTemplate::factory()->create(['utm_medium' => 'agent']);

    $shortUrl = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://example.com',
        'utm_template_id' => $template->id,
    ]);

    expect($shortUrl->utm_medium)->toBe('agent');
});

it('lets an explicit utm attribute override the template default', function () {
    $template = UtmTemplate::factory()->create(['utm_medium' => 'agent']);

    $shortUrl = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://example.com',
        'utm_template_id' => $template->id,
        'utm_medium' => 'sms',
    ]);

    expect($shortUrl->utm_medium)->toBe('sms');
});

it('rejects an update that clears a required utm parameter', function () {
    config(['short-url.utm.required' => ['utm_medium']]);
    $shortUrl = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://example.com',
        'utm_medium' => 'sms',
    ]);

    expect(fn () => app(ShortUrlManager::class)->update($shortUrl, ['utm_medium' => null]))
        ->toThrow(RequiredUtmParameterMissing::class);
});

it('allows an unrelated update to go through without re-supplying the required utm parameter', function () {
    config(['short-url.utm.required' => ['utm_medium']]);
    $shortUrl = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://example.com',
        'utm_medium' => 'sms',
    ]);

    $updated = app(ShortUrlManager::class)->update($shortUrl, ['title' => 'New title']);

    expect($updated->title)->toBe('New title')
        ->and($updated->utm_medium)->toBe('sms');
});

it('resolves a key scoped to the custom domain matching the given host', function () {
    $domain = CustomDomain::factory()->create(['domain' => 'links.example.com', 'is_verified' => true]);
    $onDomain = app(ShortUrlManager::class)->create([
        'destination_url' => 'https://a.example',
        'url_key' => 'shared01',
        'custom_domain_id' => $domain->id,
    ]);
    app(ShortUrlManager::class)->create([
        'destination_url' => 'https://b.example',
        'url_key' => 'shared01',
    ]);

    $resolved = app(ShortUrlManager::class)->resolve('shared01', 'links.example.com');

    expect($resolved?->id)->toBe($onDomain->id);
});
