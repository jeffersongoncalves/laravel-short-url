<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('archives and unarchives a short url', function () {
    $shortUrl = ShortUrl::factory()->create();

    $shortUrl->archive();
    expect($shortUrl->refresh()->archived_at)->not->toBeNull();

    $shortUrl->unarchive();
    expect($shortUrl->refresh()->archived_at)->toBeNull();
});

it('scopes archived and not-archived short urls', function () {
    $archived = ShortUrl::factory()->create(['archived_at' => now()]);
    $active = ShortUrl::factory()->create(['archived_at' => null]);

    expect(ShortUrl::archived()->pluck('id'))->toEqual(collect([$archived->id]))
        ->and(ShortUrl::notArchived()->pluck('id'))->toEqual(collect([$active->id]));
});
