<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('downloads a csv export of links', function () {
    ShortUrl::factory()->create(['url_key' => 'abc1234']);

    $response = $this->withHeaders(apiHeaders(['links:read']))
        ->get('/api/short-url/v1/export/csv')
        ->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('text/csv')
        ->and($response->getContent())->toContain('abc1234');
});
