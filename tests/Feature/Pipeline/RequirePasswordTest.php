<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\RequirePassword;
use JeffersonGoncalves\LaravelShortUrl\Support\PasswordUnlock;

function passwordContext(ShortUrl $shortUrl): RedirectContext
{
    $context = new RedirectContext(Request::create('/'.$shortUrl->url_key), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    return $context;
}

it('passes through when the short url has no password', function () {
    $shortUrl = ShortUrl::factory()->create();

    $result = (new RequirePassword)(passwordContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});

it('renders the password prompt with a 401 when locked', function () {
    $shortUrl = ShortUrl::factory()->create();
    $shortUrl->forceFill(['password_hash' => Hash::make('secret')])->save();

    $response = (new RequirePassword)(passwordContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($response->getStatusCode())->toBe(401);
});

it('passes through once the session is unlocked', function () {
    $shortUrl = ShortUrl::factory()->create();
    $shortUrl->forceFill(['password_hash' => Hash::make('secret')])->save();

    PasswordUnlock::unlock($shortUrl->id);

    $result = (new RequirePassword)(passwordContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});
