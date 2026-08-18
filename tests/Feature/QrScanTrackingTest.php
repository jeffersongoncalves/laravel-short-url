<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

it('marks a redirect via ?source=qr as a qr scan and increments qr_scans', function () {
    config(['queue.default' => 'sync']);
    $shortUrl = ShortUrl::factory()->create([
        'url_key' => 'qrscan01',
        'destination_url' => 'https://example.com/target',
    ]);

    $this->get('http://short.test/qrscan01?source=qr')->assertRedirect('https://example.com/target?source=qr');

    $visit = Visit::query()->where('short_url_id', $shortUrl->id)->first();
    expect($visit->is_qr_scan)->toBeTrue()
        ->and($shortUrl->refresh()->qr_scans)->toBe(1);
});

it('does not mark a normal visit as a qr scan', function () {
    config(['queue.default' => 'sync']);
    $shortUrl = ShortUrl::factory()->create([
        'url_key' => 'noqrscan',
        'destination_url' => 'https://example.com/target',
    ]);

    $this->get('http://short.test/noqrscan')->assertRedirect('https://example.com/target');

    expect($shortUrl->refresh()->qr_scans)->toBe(0);
});
