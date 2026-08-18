<?php

use JeffersonGoncalves\LaravelShortUrl\Models\Folder;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('supports a hierarchical parent/children relationship', function () {
    $parent = Folder::factory()->create(['name' => 'Marketing']);
    $child = Folder::factory()->create(['name' => 'Campaigns', 'parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children->pluck('id'))->toContain($child->id);
});

it('lists short urls belonging to a folder', function () {
    $folder = Folder::factory()->create();
    $shortUrl = ShortUrl::factory()->create(['folder_id' => $folder->id]);
    ShortUrl::factory()->create();

    expect($folder->shortUrls->pluck('id'))->toEqual(collect([$shortUrl->id]))
        ->and($shortUrl->folder->id)->toBe($folder->id);
});
