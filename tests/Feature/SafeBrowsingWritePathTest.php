<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Data\SafetyResult;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\UnsafeDestinationException;
use JeffersonGoncalves\LaravelShortUrl\Jobs\CheckSafeBrowsingJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

function fakeSafeBrowsing(string $status): void
{
    app()->bind(SafeBrowsingChecker::class, fn () => new class($status) implements SafeBrowsingChecker
    {
        public function __construct(protected string $status) {}

        public function check(string $url): SafetyResult
        {
            return new SafetyResult($this->status, now(), $this->status === 'unsafe' ? ['SOCIAL_ENGINEERING'] : []);
        }
    });
}

it('does not scan when safe_browsing is disabled', function () {
    config(['short-url.security.safe_browsing.enabled' => false]);
    fakeSafeBrowsing('unsafe');

    $shortUrl = ShortUrl::factory()->create();

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});

it('blocks creation synchronously when the destination is unsafe', function () {
    config(['short-url.security.safe_browsing.enabled' => true, 'short-url.security.safe_browsing.mode' => 'sync']);
    fakeSafeBrowsing('unsafe');

    expect(fn () => ShortUrl::factory()->create())->toThrow(UnsafeDestinationException::class);
});

it('allows creation and stamps safe_browsing_status when safe', function () {
    config(['short-url.security.safe_browsing.enabled' => true, 'short-url.security.safe_browsing.mode' => 'sync']);
    fakeSafeBrowsing('safe');

    $shortUrl = ShortUrl::factory()->create();

    expect($shortUrl->safe_browsing_status)->toBe('safe');
});

it('is bypassed when the bypass flag is set', function () {
    config([
        'short-url.security.safe_browsing.enabled' => true,
        'short-url.security.safe_browsing.mode' => 'sync',
        'short-url.security.safe_browsing.bypass' => true,
    ]);
    fakeSafeBrowsing('unsafe');

    $shortUrl = ShortUrl::factory()->create();

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});

it('dispatches an async check instead of blocking in async mode', function () {
    Bus::fake();
    config(['short-url.security.safe_browsing.enabled' => true, 'short-url.security.safe_browsing.mode' => 'async']);
    fakeSafeBrowsing('unsafe');

    $shortUrl = ShortUrl::factory()->create();

    Bus::assertDispatched(CheckSafeBrowsingJob::class, fn (CheckSafeBrowsingJob $job) => $job->shortUrlId === $shortUrl->id);
});
