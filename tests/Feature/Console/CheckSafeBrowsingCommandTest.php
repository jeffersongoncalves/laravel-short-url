<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Jobs\CheckSafeBrowsingJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('does nothing when safe_browsing is disabled', function () {
    Bus::fake();
    config(['short-url.security.safe_browsing.enabled' => false]);
    ShortUrl::factory()->create();

    $this->artisan('short-url:check-safe-browsing')->assertExitCode(0);

    Bus::assertNotDispatched(CheckSafeBrowsingJob::class);
});

it('dispatches a check for every enabled short url never checked before', function () {
    Bus::fake();

    // Created before enabling safe_browsing, so the write-path scan doesn't
    // stamp safe_browsing_checked_at itself and mask what we're testing.
    $shortUrl = ShortUrl::factory()->create(['is_enabled' => true, 'safe_browsing_checked_at' => null]);
    ShortUrl::factory()->create(['is_enabled' => false]);

    config(['short-url.security.safe_browsing.enabled' => true]);

    $this->artisan('short-url:check-safe-browsing')->assertExitCode(0);

    Bus::assertDispatched(CheckSafeBrowsingJob::class, fn (CheckSafeBrowsingJob $job) => $job->shortUrlId === $shortUrl->id);
    Bus::assertDispatchedTimes(CheckSafeBrowsingJob::class, 1);
});
