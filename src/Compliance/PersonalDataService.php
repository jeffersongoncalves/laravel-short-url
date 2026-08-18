<?php

namespace JeffersonGoncalves\LaravelShortUrl\Compliance;

use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Support\IpAnonymizer;

/**
 * LGPD/GDPR data-subject requests, scoped by IP (the only personally
 * identifying key visits are stored under). Matching is limited to
 * whatever the current SHORT_URL_IP_HASH_SALT was in effect when a visit
 * was recorded — that's by design (see short-url.tracking.ip_hash_salt).
 */
class PersonalDataService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportForIp(string $ip): array
    {
        return Visit::query()
            ->where('ip_hash', IpAnonymizer::hash($ip))
            ->get()
            ->map(fn (Visit $visit) => $visit->toArray())
            ->all();
    }

    /**
     * Right to erasure: strips identifying fields from matching visits
     * in place rather than deleting the rows, so historical aggregate
     * counts (already folded into daily_stats) stay accurate.
     */
    public function forgetForIp(string $ip): int
    {
        return Visit::query()
            ->where('ip_hash', IpAnonymizer::hash($ip))
            ->update([
                'ip_hash' => null,
                'ip_anonymized' => null,
                'ip_version' => null,
                'user_agent_hash' => null,
            ]);
    }
}
