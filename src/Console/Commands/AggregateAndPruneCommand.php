<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;

/**
 * Folds a day's raw visits into short_url_daily_stats, then prunes visit
 * rows past the configured retention window. Defaults to aggregating
 * yesterday (today is still live and served straight off short_url_visits
 * by StatsAggregator).
 */
class AggregateAndPruneCommand extends Command
{
    protected $signature = 'short-url:aggregate-and-prune {--date= : Date to aggregate (Y-m-d), defaults to yesterday}';

    protected $description = 'Aggregate visits into daily_stats and prune expired visit rows.';

    public function handle(VisitRepository $visits): int
    {
        $date = $this->option('date') ? Carbon::parse((string) $this->option('date')) : Carbon::yesterday();

        $this->aggregate($visits, $date);
        $this->prune($visits);

        return self::SUCCESS;
    }

    protected function aggregate(VisitRepository $visits, Carbon $date): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $shortUrlIds = DB::table($prefix.'visits')
            ->whereBetween('visited_at', [$from, $to])
            ->groupBy('short_url_id')
            ->pluck('short_url_id');

        foreach ($shortUrlIds as $shortUrlId) {
            $stats = $visits->aggregate((int) $shortUrlId, $from, $to);

            DB::table($prefix.'daily_stats')->updateOrInsert(
                ['short_url_id' => $shortUrlId, 'date' => $date->toDateString()],
                [
                    'visits_count' => $stats['visits_count'],
                    'unique_visits_count' => $stats['unique_visits_count'],
                    'qr_visits_count' => $stats['qr_visits_count'],
                    'bot_visits_count' => $stats['bot_visits_count'],
                    'device_stats' => json_encode($stats['device_stats']),
                    'browser_stats' => json_encode($stats['browser_stats']),
                    'os_stats' => json_encode($stats['os_stats']),
                    'country_stats' => json_encode($stats['country_stats']),
                    'city_stats' => json_encode($stats['city_stats']),
                    'referer_stats' => json_encode($stats['referer_stats']),
                    'referer_type_stats' => json_encode($stats['referer_type_stats']),
                    'utm_source_stats' => json_encode($stats['utm_source_stats']),
                    'utm_medium_stats' => json_encode($stats['utm_medium_stats']),
                    'utm_campaign_stats' => json_encode($stats['utm_campaign_stats']),
                    'language_stats' => json_encode($stats['language_stats']),
                    'variant_stats' => json_encode($stats['variant_stats']),
                    'hourly_stats' => json_encode($stats['hourly_stats']),
                    'updated_at' => now(),
                ]
            );
        }
    }

    protected function prune(VisitRepository $visits): void
    {
        $retentionDays = (int) config('short-url.tracking.retention_days', 400);

        $visits->prune(now()->subDays($retentionDays));
    }
}
