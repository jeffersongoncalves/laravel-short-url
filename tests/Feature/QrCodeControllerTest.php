<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('serves an svg qr code by default', function () {
    ShortUrl::factory()->create(['url_key' => 'qr123456']);

    $this->get('http://short.test/qr123456/qr')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

it('serves a png qr code', function () {
    ShortUrl::factory()->create(['url_key' => 'qr123456']);

    $response = $this->get('http://short.test/qr123456/qr?format=png')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/png');
});

it('returns 404 for an unsupported format', function () {
    ShortUrl::factory()->create(['url_key' => 'qr123456']);

    $this->get('http://short.test/qr123456/qr?format=bmp')->assertNotFound();
});

it('returns 404 for a missing short url', function () {
    $this->get('http://short.test/does-not-exist/qr')->assertNotFound();
});
