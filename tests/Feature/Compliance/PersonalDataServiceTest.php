<?php

use JeffersonGoncalves\LaravelShortUrl\Compliance\PersonalDataService;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Support\IpAnonymizer;

it('exports every visit matching the given ip', function () {
    $shortUrl = ShortUrl::factory()->create();
    $ip = '203.0.113.42';

    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'ip_hash' => IpAnonymizer::hash($ip),
        'is_bot' => false,
        'created_at' => now(),
    ]);
    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'ip_hash' => IpAnonymizer::hash('198.51.100.7'),
        'is_bot' => false,
        'created_at' => now(),
    ]);

    $export = (new PersonalDataService)->exportForIp($ip);

    expect($export)->toHaveCount(1);
});

it('strips identifying fields for a subject without deleting the visit row', function () {
    $shortUrl = ShortUrl::factory()->create();
    $ip = '203.0.113.42';

    $visit = Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'ip_hash' => IpAnonymizer::hash($ip),
        'ip_anonymized' => IpAnonymizer::truncate($ip),
        'user_agent_hash' => hash('sha256', 'some-ua'),
        'is_bot' => false,
        'created_at' => now(),
    ]);

    $affected = (new PersonalDataService)->forgetForIp($ip);

    $visit->refresh();

    expect($affected)->toBe(1)
        ->and($visit->ip_hash)->toBeNull()
        ->and($visit->ip_anonymized)->toBeNull()
        ->and($visit->user_agent_hash)->toBeNull()
        ->and(Visit::query()->count())->toBe(1);
});
