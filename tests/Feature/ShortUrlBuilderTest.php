<?php

use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlBuilder;

it('hashes the password instead of storing it as plaintext', function () {
    $shortUrl = (new ShortUrlBuilder('https://example.com'))
        ->key('pwd1234')
        ->password('secret')
        ->create();

    expect($shortUrl->password_hash)->not->toBe('secret')
        ->and(Hash::check('secret', $shortUrl->password_hash))->toBeTrue();
});

it('applies sensible defaults when creating without explicit attributes', function () {
    $shortUrl = (new ShortUrlBuilder('https://example.com'))->create();

    expect($shortUrl->is_enabled)->toBeTrue()
        ->and($shortUrl->forward_query_params)->toBeTrue()
        ->and($shortUrl->destination_type)->toBe('single')
        ->and($shortUrl->url_key)->not->toBeEmpty();
});

it('sets custom_domain_id via customDomain()', function () {
    $domain = CustomDomain::factory()->create();

    $shortUrl = (new ShortUrlBuilder('https://example.com'))->customDomain($domain->id)->create();

    expect($shortUrl->custom_domain_id)->toBe($domain->id);
});

it('sets utm attributes directly via utm()', function () {
    $shortUrl = (new ShortUrlBuilder('https://example.com'))
        ->utm(['utm_medium' => 'sms', 'utm_campaign' => 'launch'])
        ->create();

    expect($shortUrl->utm_medium)->toBe('sms')
        ->and($shortUrl->utm_campaign)->toBe('launch');
});

it('fills utm attributes from a template via utmTemplate()', function () {
    $template = UtmTemplate::factory()->create(['utm_medium' => 'email', 'utm_source' => 'newsletter']);

    $shortUrl = (new ShortUrlBuilder('https://example.com'))->utmTemplate($template->id)->create();

    expect($shortUrl->utm_medium)->toBe('email')
        ->and($shortUrl->utm_source)->toBe('newsletter');
});
