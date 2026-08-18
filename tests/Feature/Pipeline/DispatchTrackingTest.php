<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Events\ShortUrlVisited;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\DispatchTracking;

beforeEach(function () {
    config(['queue.default' => 'sync']);
});

function dispatchTrackingContext(ShortUrl $shortUrl, array $trackingOverrides = []): RedirectContext
{
    // Mirrors production: ResolveShortUrl always hands the pipeline a
    // freshly-loaded model, so track_* flags reflect real DB column
    // defaults instead of the in-memory just-created instance.
    $shortUrl = $shortUrl->fresh();

    $request = Request::create('/'.$shortUrl->url_key, 'GET', ['utm_source' => 'newsletter']);
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0');
    $request->headers->set('referer', 'https://www.google.com/search?q=x');

    $context = new RedirectContext($request, $shortUrl->url_key);
    $context->shortUrl = $shortUrl;
    $context->host = 'short.test';
    $context->tracking = array_merge([
        'is_bot' => false,
        'device_type' => 'desktop',
        'operating_system' => 'Windows',
    ], $trackingOverrides);

    return $context;
}

it('stores a visit and increments counters synchronously on the sync queue', function () {
    $shortUrl = ShortUrl::factory()->create(['track_visits' => true]);

    (new DispatchTracking)(dispatchTrackingContext($shortUrl), fn (RedirectContext $c) => $c);

    $shortUrl->refresh();
    $visit = Visit::query()->where('short_url_id', $shortUrl->id)->first();

    expect($visit)->not->toBeNull()
        ->and($visit->is_bot)->toBeFalse()
        ->and($visit->device_type)->toBe('desktop')
        ->and($visit->utm_source)->toBe('newsletter')
        ->and($visit->referer_type)->toBe('search')
        ->and($shortUrl->total_visits)->toBe(1)
        ->and($shortUrl->unique_visits)->toBe(1)
        ->and($shortUrl->last_visited_at)->not->toBeNull();
});

it('does not count a bot visit toward total or unique visits', function () {
    $shortUrl = ShortUrl::factory()->create(['track_visits' => true]);

    (new DispatchTracking)(dispatchTrackingContext($shortUrl, ['is_bot' => true]), fn (RedirectContext $c) => $c);

    $shortUrl->refresh();

    expect($shortUrl->total_visits)->toBe(0)
        ->and($shortUrl->bot_visits)->toBe(1);
});

it('skips tracking entirely when track_visits is disabled', function () {
    $shortUrl = ShortUrl::factory()->create(['track_visits' => false]);

    (new DispatchTracking)(dispatchTrackingContext($shortUrl), fn (RedirectContext $c) => $c);

    expect(Visit::query()->count())->toBe(0);
});

it('dispatches the ShortUrlVisited event after storing', function () {
    Event::fake([ShortUrlVisited::class]);

    $shortUrl = ShortUrl::factory()->create(['track_visits' => true]);

    (new DispatchTracking)(dispatchTrackingContext($shortUrl), fn (RedirectContext $c) => $c);

    Event::assertDispatched(ShortUrlVisited::class);
});

it('never lets a tracking failure escape the stage', function () {
    app()->bind(VisitRepository::class, function () {
        return new class implements VisitRepository
        {
            public function store(array $attributes): void
            {
                throw new RuntimeException('boom');
            }

            public function query(int $shortUrlId, array $filters = []): array
            {
                return [];
            }

            public function aggregate(int $shortUrlId, DateTimeInterface $from, DateTimeInterface $to): array
            {
                return [];
            }

            public function prune(DateTimeInterface $before): int
            {
                return 0;
            }
        };
    });

    $shortUrl = ShortUrl::factory()->create(['track_visits' => true]);
    $context = dispatchTrackingContext($shortUrl);

    $result = (new DispatchTracking)($context, fn (RedirectContext $c) => $c);

    expect($result)->toBe($context)
        ->and(Visit::query()->count())->toBe(0);
});
