<?php

namespace JeffersonGoncalves\LaravelShortUrl\Compliance;

use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\VisitorFingerprint\Compliance\PersonalDataExporter;

/**
 * LGPD/GDPR data-subject requests, scoped by IP (the only personally
 * identifying key visits are stored under). Matching is limited to
 * whatever hash salt was in effect when a visit was recorded — that's by
 * design (see visitor-fingerprint.hash_salt). The actual export/erasure
 * logic lives in laravel-visitor-fingerprint's PersonalDataExporter; this
 * class is just the Visit-model-specific glue.
 */
class PersonalDataService
{
    /**
     * @var PersonalDataExporter<Visit>
     */
    protected PersonalDataExporter $exporter;

    public function __construct()
    {
        $this->exporter = new PersonalDataExporter(Visit::class, 'ip_hash');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportForIp(string $ip): array
    {
        return $this->exporter->exportForIp($ip);
    }

    /**
     * Right to erasure: strips identifying fields from matching visits
     * in place rather than deleting the rows, so historical aggregate
     * counts (already folded into daily_stats) stay accurate.
     */
    public function forgetForIp(string $ip): int
    {
        return $this->exporter->forgetForIp($ip);
    }
}
