<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Policies\ShortUrlPolicy;

it('registers the policy for the ShortUrl model', function () {
    $user = new Authenticatable;
    $shortUrl = ShortUrl::factory()->create();

    expect($user->can('viewAny', ShortUrl::class))->toBeTrue()
        ->and($user->can('view', $shortUrl))->toBeTrue()
        ->and($user->can('create', ShortUrl::class))->toBeTrue()
        ->and($user->can('update', $shortUrl))->toBeTrue()
        ->and($user->can('delete', $shortUrl))->toBeTrue();
});

it('allows all abilities by default', function () {
    $policy = new ShortUrlPolicy;
    $user = new Authenticatable;
    $shortUrl = ShortUrl::factory()->create();

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->deleteAny($user))->toBeTrue()
        ->and($policy->update($user, $shortUrl))->toBeTrue();
});
