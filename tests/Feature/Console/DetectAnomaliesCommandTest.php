<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;

it('raises a visit_spike alert when today wildly exceeds the baseline', function () {
    Bus::fake();
    $shortUrl = ShortUrl::factory()->create();
    Webhook::factory()->create(['short_url_id' => null, 'events' => ['alert.triggered']]);

    $prefix = config('short-url.table_prefix', 'short_url_');
    foreach ([8, 10, 12, 9, 11, 10, 10] as $offset => $count) {
        DB::table($prefix.'daily_stats')->insert([
            'short_url_id' => $shortUrl->id,
            'date' => now()->subDays($offset + 1)->toDateString(),
            'visits_count' => $count,
            'unique_visits_count' => $count,
            'qr_visits_count' => 0,
            'bot_visits_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    foreach (range(1, 50) as $i) {
        Visit::query()->create([
            'short_url_id' => $shortUrl->id,
            'visited_at' => now(),
            'is_bot' => false,
            'ip_hash' => "hash-{$i}",
            'created_at' => now(),
        ]);
    }

    $this->artisan('short-url:detect-anomalies')->assertExitCode(0);

    $alert = Alert::query()->where('short_url_id', $shortUrl->id)->first();

    expect($alert)->not->toBeNull()
        ->and($alert->type)->toBe('visit_spike');

    Bus::assertDispatched(SendWebhookJob::class);
});

it('skips a link with fewer than 3 days of baseline history', function () {
    $shortUrl = ShortUrl::factory()->create();

    $this->artisan('short-url:detect-anomalies')->assertExitCode(0);

    expect(Alert::query()->where('short_url_id', $shortUrl->id)->exists())->toBeFalse();
});
