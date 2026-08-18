<?php

use Illuminate\Database\QueryException;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Tag;

it('attaches multiple tags to a short url', function () {
    $shortUrl = ShortUrl::factory()->create();
    $tagA = Tag::factory()->create(['name' => 'campaign-a']);
    $tagB = Tag::factory()->create(['name' => 'campaign-b']);

    $shortUrl->tags()->attach([$tagA->id, $tagB->id]);

    expect($shortUrl->tags->pluck('name')->sort()->values())->toEqual(collect(['campaign-a', 'campaign-b']))
        ->and($tagA->shortUrls->pluck('id'))->toContain($shortUrl->id);
});

it('enforces a unique tag name per tenant', function () {
    // NULL is not equal to NULL in a unique index (SQL standard) — use a
    // real tenant id, otherwise the constraint never actually engages.
    Tag::factory()->create(['name' => 'launch', 'tenant_id' => 1]);

    expect(fn () => Tag::factory()->create(['name' => 'launch', 'tenant_id' => 1]))
        ->toThrow(QueryException::class);
});
