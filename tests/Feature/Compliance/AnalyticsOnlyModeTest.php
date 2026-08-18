<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\DispatchTracking;

it('never stores ip or user-agent hashes when analytics_only is on', function () {
    config(['queue.default' => 'sync', 'short-url.compliance.analytics_only' => true]);

    $shortUrl = ShortUrl::factory()->create(['track_visits' => true, 'track_ip_address' => true])->fresh();

    $request = Request::create('/'.$shortUrl->url_key, 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.42']);
    $context = new RedirectContext($request, $shortUrl->url_key);
    $context->shortUrl = $shortUrl;
    $context->host = 'short.test';

    (new DispatchTracking)($context, fn (RedirectContext $c) => $c);

    $visit = Visit::query()->where('short_url_id', $shortUrl->id)->first();

    expect($visit->ip_hash)->toBeNull()
        ->and($visit->ip_anonymized)->toBeNull()
        ->and($visit->user_agent_hash)->toBeNull();
});
