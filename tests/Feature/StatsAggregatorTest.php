<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

it('merges historical daily_stats with live visits from today', function () {
    $shortUrl = ShortUrl::factory()->create();
    $prefix = config('short-url.table_prefix', 'short_url_');

    DB::table($prefix.'daily_stats')->insert([
        'short_url_id' => $shortUrl->id,
        'date' => now()->subDay()->toDateString(),
        'visits_count' => 4,
        'unique_visits_count' => 3,
        'qr_visits_count' => 1,
        'bot_visits_count' => 0,
        'device_stats' => json_encode(['desktop' => 4]),
        'browser_stats' => json_encode([]),
        'os_stats' => json_encode([]),
        'country_stats' => json_encode(['BR' => 4]),
        'city_stats' => json_encode([]),
        'referer_stats' => json_encode([]),
        'referer_type_stats' => json_encode([]),
        'utm_source_stats' => json_encode([]),
        'utm_medium_stats' => json_encode([]),
        'utm_campaign_stats' => json_encode([]),
        'language_stats' => json_encode([]),
        'variant_stats' => json_encode([]),
        'hourly_stats' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'country_code' => 'BR',
        'is_bot' => false,
        'ip_hash' => 'today-hash',
        'created_at' => now(),
    ]);

    $payload = app(StatsAggregator::class)
        ->for($shortUrl)
        ->between(now()->subDays(2), now())
        ->get();

    expect($payload->totalVisits)->toBe(5)
        ->and($payload->uniqueVisits)->toBe(4)
        ->and($payload->countryStats)->toBe(['BR' => 5]);
});
