<?php

use Illuminate\Support\Facades\Hash;
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
