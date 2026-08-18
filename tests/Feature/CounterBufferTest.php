<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;

it('increments counters directly in the database when buffering is disabled', function () {
    config(['short-url.tracking.counter_buffering' => false]);
    config(['queue.default' => 'sync']);

    $shortUrl = ShortUrl::factory()->create(['total_visits' => 2, 'bot_visits' => 0]);

    app(CounterBuffer::class)->increment($shortUrl->id, ['total_visits' => 3, 'bot_visits' => 1]);

    $shortUrl->refresh();

    expect($shortUrl->total_visits)->toBe(5)
        ->and($shortUrl->bot_visits)->toBe(1);
});

it('falls back to a direct increment when the redis connection is unreachable', function () {
    config(['short-url.tracking.counter_buffering' => true]);
    config(['queue.default' => 'sync']);
    config(['database.redis.short_url_test_unreachable' => [
        'host' => '127.0.0.1',
        'port' => 1,
        'timeout' => 0.1,
    ]]);
    config(['short-url.tracking.redis_connection' => 'short_url_test_unreachable']);

    $shortUrl = ShortUrl::factory()->create(['total_visits' => 0]);

    app(CounterBuffer::class)->increment($shortUrl->id, ['total_visits' => 1]);

    $shortUrl->refresh();

    expect($shortUrl->total_visits)->toBe(1);
});

it('ignores an all-zero counter delta', function () {
    $shortUrl = ShortUrl::factory()->create(['total_visits' => 0]);

    app(CounterBuffer::class)->increment($shortUrl->id, ['total_visits' => 0]);

    expect($shortUrl->refresh()->total_visits)->toBe(0);
});
