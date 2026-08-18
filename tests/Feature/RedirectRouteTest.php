<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('redirects to the destination for an enabled short url', function () {
    ShortUrl::factory()->create([
        'url_key' => 'go12345',
        'destination_url' => 'https://example.com/target',
        'redirect_status_code' => 302,
    ]);

    $this->get('http://short.test/go12345')
        ->assertRedirect('https://example.com/target');
});

it('returns 404 for a disabled short url', function () {
    ShortUrl::factory()->create(['url_key' => 'off1234', 'is_enabled' => false]);

    $this->get('http://short.test/off1234')->assertNotFound();
});

it('returns 404 for a missing short url', function () {
    $this->get('http://short.test/does-not-exist')->assertNotFound();
});

it('renders the branded expired page for an expired short url without a fallback', function () {
    ShortUrl::factory()->create(['url_key' => 'exp1234', 'expires_at' => now()->subDay()]);

    $this->get('http://short.test/exp1234')->assertStatus(410);
});
