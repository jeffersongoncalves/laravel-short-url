<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

it('folds yesterdays visits into a daily_stats row and prunes old visits', function () {
    config(['short-url.tracking.retention_days' => 30]);

    $shortUrl = ShortUrl::factory()->create();

    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now()->subDay()->setTime(10, 0),
        'country_code' => 'BR',
        'is_bot' => false,
        'is_qr_scan' => false,
        'ip_hash' => 'hash-1',
        'created_at' => now()->subDay(),
    ]);
    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now()->subDays(90),
        'is_bot' => false,
        'ip_hash' => 'hash-2',
        'created_at' => now()->subDays(90),
    ]);

    $this->artisan('short-url:aggregate-and-prune')->assertExitCode(0);

    $prefix = config('short-url.table_prefix', 'short_url_');
    $row = DB::table($prefix.'daily_stats')
        ->where('short_url_id', $shortUrl->id)
        ->where('date', now()->subDay()->toDateString())
        ->first();

    expect($row)->not->toBeNull()
        ->and((int) $row->visits_count)->toBe(1)
        ->and(json_decode((string) $row->country_stats, true))->toBe(['BR' => 1])
        ->and(Visit::query()->count())->toBe(1);
});
