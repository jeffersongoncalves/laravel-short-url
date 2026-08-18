<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('redirects using the fallback route when enabled', function () {
    ShortUrl::factory()->create([
        'url_key' => 'fb12345',
        'destination_url' => 'https://example.com/fb',
    ]);

    $this->get('http://short.test/fb12345')->assertRedirect('https://example.com/fb');
});

it('still returns 404 for a missing key via the fallback route', function () {
    $this->get('http://short.test/does-not-exist')->assertNotFound();
});
