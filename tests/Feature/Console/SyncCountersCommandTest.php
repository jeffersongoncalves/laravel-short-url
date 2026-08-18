<?php

it('runs cleanly when counter buffering is disabled', function () {
    config(['short-url.tracking.counter_buffering' => false]);

    $this->artisan('short-url:sync-counters')->assertExitCode(0);
});

it('runs cleanly when buffering is enabled but redis is unreachable', function () {
    config(['short-url.tracking.counter_buffering' => true]);
    config(['database.redis.short_url_test_unreachable' => [
        'host' => '127.0.0.1',
        'port' => 1,
        'timeout' => 0.1,
    ]]);
    config(['short-url.tracking.redis_connection' => 'short_url_test_unreachable']);

    $this->artisan('short-url:sync-counters')->assertExitCode(0);
});
