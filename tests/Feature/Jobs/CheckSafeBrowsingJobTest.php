<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Data\SafetyResult;
use JeffersonGoncalves\LaravelShortUrl\Jobs\CheckSafeBrowsingJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('updates the short url safe_browsing_status without re-triggering the observer', function () {
    $shortUrl = ShortUrl::factory()->create(['destination_url' => 'https://example.com']);

    app()->bind(SafeBrowsingChecker::class, fn () => new class implements SafeBrowsingChecker
    {
        public function check(string $url): SafetyResult
        {
            return new SafetyResult('unsafe', now(), ['MALWARE']);
        }
    });

    (new CheckSafeBrowsingJob($shortUrl->id))->handle(app(SafeBrowsingChecker::class));

    $shortUrl->refresh();

    expect($shortUrl->safe_browsing_status)->toBe('unsafe')
        ->and($shortUrl->safe_browsing_checked_at)->not->toBeNull();
});

it('does nothing when the short url no longer exists', function () {
    app()->bind(SafeBrowsingChecker::class, fn () => new class implements SafeBrowsingChecker
    {
        public function check(string $url): SafetyResult
        {
            return new SafetyResult('safe', now());
        }
    });

    (new CheckSafeBrowsingJob(999999))->handle(app(SafeBrowsingChecker::class));
})->throwsNoExceptions();
