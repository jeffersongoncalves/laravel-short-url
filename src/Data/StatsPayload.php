<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class StatsPayload
{
    /**
     * @param  array<string, int>  $deviceStats
     * @param  array<string, int>  $browserStats
     * @param  array<string, int>  $osStats
     * @param  array<string, int>  $countryStats
     * @param  array<string, int>  $cityStats
     * @param  array<string, int>  $refererStats
     * @param  array<string, int>  $refererTypeStats
     * @param  array<string, int>  $utmSourceStats
     * @param  array<string, int>  $utmMediumStats
     * @param  array<string, int>  $utmCampaignStats
     * @param  array<string, int>  $languageStats
     * @param  array<string, int>  $variantStats
     * @param  array<int, int>  $hourlyStats
     */
    public function __construct(
        public int $totalVisits = 0,
        public int $uniqueVisits = 0,
        public int $qrVisits = 0,
        public int $botVisits = 0,
        public array $deviceStats = [],
        public array $browserStats = [],
        public array $osStats = [],
        public array $countryStats = [],
        public array $cityStats = [],
        public array $refererStats = [],
        public array $refererTypeStats = [],
        public array $utmSourceStats = [],
        public array $utmMediumStats = [],
        public array $utmCampaignStats = [],
        public array $languageStats = [],
        public array $variantStats = [],
        public array $hourlyStats = [],
    ) {}
}
