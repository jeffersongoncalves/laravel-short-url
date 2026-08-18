<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Security\DestinationUrlCollector;

it('collects just the base url for a single destination', function () {
    $shortUrl = ShortUrl::factory()->create(['destination_url' => 'https://example.com/base']);

    expect(DestinationUrlCollector::collect($shortUrl))->toBe(['https://example.com/base']);
});

it('collects every rotation variant url', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_url' => 'https://example.com/base',
        'rotation_variants' => [
            ['url' => 'https://a.test', 'weight' => 50],
            ['url' => 'https://b.test', 'weight' => 50],
        ],
    ]);

    expect(DestinationUrlCollector::collect($shortUrl))->toContain('https://a.test', 'https://b.test');
});

it('collects rule destinations and nested split urls', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            ['conditions' => [], 'destination' => 'https://rule.test'],
            ['conditions' => [], 'split' => [['url' => 'https://nested.test', 'weight' => 100]]],
        ],
    ]);

    expect(DestinationUrlCollector::collect($shortUrl))->toContain('https://rule.test', 'https://nested.test');
});
