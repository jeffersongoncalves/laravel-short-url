<?php

namespace JeffersonGoncalves\LaravelShortUrl\Stats;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Data\StatsPayload;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Targeting\SignificanceCalculator;
use RuntimeException;

/**
 * Merges finalized short_url_daily_stats rows (any day before today) with
 * a live aggregate of today's short_url_visits rows, so callers always get
 * up-to-the-minute numbers without re-scanning historical raw visits.
 */
class EloquentStatsAggregator implements StatsAggregator
{
    protected ?ShortUrl $shortUrl = null;

    /**
     * @var array<int, int>|null
     */
    protected ?array $shortUrlIds = null;

    protected ?DateTimeInterface $from = null;

    protected ?DateTimeInterface $to = null;

    public function __construct(protected VisitRepository $visits) {}

    public function for(ShortUrl $shortUrl): static
    {
        $this->shortUrl = $shortUrl;
        $this->shortUrlIds = null;

        return $this;
    }

    public function forShortUrls(array $shortUrlIds): static
    {
        $this->shortUrlIds = $shortUrlIds;
        $this->shortUrl = null;

        return $this;
    }

    public function between(DateTimeInterface $from, DateTimeInterface $to): static
    {
        $this->from = $from;
        $this->to = $to;

        return $this;
    }

    public function get(): StatsPayload
    {
        if ((! $this->shortUrl && $this->shortUrlIds === null) || ! $this->from || ! $this->to) {
            throw new RuntimeException('Call for() or forShortUrls(), and between(), before get().');
        }

        $totals = $this->emptyTotals();
        $today = Carbon::today();
        $from = Carbon::instance($this->from);
        $to = Carbon::instance($this->to);

        $historicalTo = $to->lessThan($today) ? $to : $today->copy()->subDay();

        if ($from->lessThanOrEqualTo($historicalTo)) {
            $this->mergeHistorical($totals, $from, $historicalTo);
        }

        if ($to->greaterThanOrEqualTo($today)) {
            $liveFrom = $from->greaterThan($today) ? $from : $today;
            $liveTotals = $this->shortUrl
                ? $this->visits->aggregate($this->shortUrl->id, $liveFrom, $to)
                : $this->visits->aggregateMany($this->shortUrlIds ?? [], $liveFrom, $to);
            $this->merge($totals, $liveTotals);
        }

        // A "variant" label is only meaningful within the link it was
        // defined on — mixing labels across unrelated links in a
        // cross-link breakdown would conflate different A/B tests that
        // happen to share a label, so both are left empty outside for().
        $variantStats = $this->shortUrl ? $totals['variant_stats'] : [];

        return new StatsPayload(
            totalVisits: $totals['visits_count'],
            uniqueVisits: $totals['unique_visits_count'],
            qrVisits: $totals['qr_visits_count'],
            botVisits: $totals['bot_visits_count'],
            deviceStats: $totals['device_stats'],
            browserStats: $totals['browser_stats'],
            osStats: $totals['os_stats'],
            countryStats: $totals['country_stats'],
            cityStats: $totals['city_stats'],
            refererStats: $totals['referer_stats'],
            refererTypeStats: $totals['referer_type_stats'],
            utmSourceStats: $totals['utm_source_stats'],
            utmMediumStats: $totals['utm_medium_stats'],
            utmCampaignStats: $totals['utm_campaign_stats'],
            languageStats: $totals['language_stats'],
            variantStats: $variantStats,
            hourlyStats: $totals['hourly_stats'],
            variantSignificance: $this->variantSignificance($variantStats),
        );
    }

    /**
     * @param  array<string, mixed>  $totals
     */
    protected function mergeHistorical(array &$totals, Carbon $from, Carbon $to): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        $rows = DB::table($prefix.'daily_stats')
            ->when(
                $this->shortUrl,
                fn ($query) => $query->where('short_url_id', $this->shortUrl->id),
                fn ($query) => $query->whereIn('short_url_id', $this->shortUrlIds ?: [0]),
            )
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        foreach ($rows as $row) {
            $this->merge($totals, [
                'visits_count' => $row->visits_count,
                'unique_visits_count' => $row->unique_visits_count,
                'qr_visits_count' => $row->qr_visits_count,
                'bot_visits_count' => $row->bot_visits_count,
                'device_stats' => json_decode((string) $row->device_stats, true) ?? [],
                'browser_stats' => json_decode((string) $row->browser_stats, true) ?? [],
                'os_stats' => json_decode((string) $row->os_stats, true) ?? [],
                'country_stats' => json_decode((string) $row->country_stats, true) ?? [],
                'city_stats' => json_decode((string) $row->city_stats, true) ?? [],
                'referer_stats' => json_decode((string) $row->referer_stats, true) ?? [],
                'referer_type_stats' => json_decode((string) $row->referer_type_stats, true) ?? [],
                'utm_source_stats' => json_decode((string) $row->utm_source_stats, true) ?? [],
                'utm_medium_stats' => json_decode((string) $row->utm_medium_stats, true) ?? [],
                'utm_campaign_stats' => json_decode((string) $row->utm_campaign_stats, true) ?? [],
                'language_stats' => json_decode((string) $row->language_stats, true) ?? [],
                'variant_stats' => json_decode((string) $row->variant_stats, true) ?? [],
                'hourly_stats' => json_decode((string) $row->hourly_stats, true) ?? [],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $addition
     */
    protected function merge(array &$totals, array $addition): void
    {
        foreach (['visits_count', 'unique_visits_count', 'qr_visits_count', 'bot_visits_count'] as $key) {
            $totals[$key] += (int) ($addition[$key] ?? 0);
        }

        $dimensions = [
            'device_stats', 'browser_stats', 'os_stats', 'country_stats', 'city_stats',
            'referer_stats', 'referer_type_stats', 'utm_source_stats', 'utm_medium_stats',
            'utm_campaign_stats', 'language_stats', 'variant_stats', 'hourly_stats',
        ];

        foreach ($dimensions as $key) {
            foreach ((array) ($addition[$key] ?? []) as $label => $count) {
                $totals[$key][$label] = ($totals[$key][$label] ?? 0) + $count;
            }
        }
    }

    /**
     * @param  array<string, int>  $variantStats
     * @return array<string, float>
     */
    protected function variantSignificance(array $variantStats): array
    {
        if (count($variantStats) < 2) {
            return [];
        }

        arsort($variantStats);
        $total = array_sum($variantStats);
        $control = array_key_first($variantStats);
        $controlCount = $variantStats[$control];

        $significance = [];

        foreach ($variantStats as $variant => $count) {
            if ($variant === $control) {
                continue;
            }

            $zScore = SignificanceCalculator::zScore($count, $total, $controlCount, $total);

            if ($zScore !== null) {
                $significance[$variant] = round($zScore, 4);
            }
        }

        return $significance;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyTotals(): array
    {
        return [
            'visits_count' => 0,
            'unique_visits_count' => 0,
            'qr_visits_count' => 0,
            'bot_visits_count' => 0,
            'device_stats' => [],
            'browser_stats' => [],
            'os_stats' => [],
            'country_stats' => [],
            'city_stats' => [],
            'referer_stats' => [],
            'referer_type_stats' => [],
            'utm_source_stats' => [],
            'utm_medium_stats' => [],
            'utm_campaign_stats' => [],
            'language_stats' => [],
            'variant_stats' => [],
            'hourly_stats' => [],
        ];
    }
}
