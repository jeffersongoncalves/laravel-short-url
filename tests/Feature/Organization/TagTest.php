<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

    // DB::transaction() wraps the failing insert in its own SAVEPOINT —
    // on Postgres, a caught constraint violation still aborts the whole
    // enclosing transaction (including RefreshDatabase's per-test one)
    // until something rolls back to a savepoint.
    expect(fn () => DB::transaction(fn () => Tag::factory()->create(['name' => 'launch', 'tenant_id' => 1])))
        ->toThrow(QueryException::class);
});
