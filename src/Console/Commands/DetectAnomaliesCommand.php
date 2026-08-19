<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Events\AlertTriggered;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Notifications\AlertNotification;

/**
 * Flags today's live visit count as anomalous when it strays too many
 * standard deviations from the trailing baseline (short-url.alerts.
 * baseline_days of daily_stats). Needs at least 3 days of history — a
 * link too young to have a baseline is silently skipped, not flagged.
 */
class DetectAnomaliesCommand extends Command
{
    protected $signature = 'short-url:detect-anomalies';

    protected $description = 'Compare each link\'s live visit count against its trailing baseline and raise alerts on outliers.';

    public function handle(VisitRepository $visits): int
    {
        $baselineDays = (int) config('short-url.alerts.baseline_days', 7);
        $threshold = (float) config('short-url.alerts.anomaly_z_threshold', 3.0);
        $prefix = config('short-url.table_prefix', 'short_url_');

        foreach (ShortUrl::query()->enabled()->pluck('id') as $shortUrlId) {
            $baseline = DB::table($prefix.'daily_stats')
                ->where('short_url_id', $shortUrlId)
                ->where('date', '>=', now()->subDays($baselineDays)->toDateString())
                ->where('date', '<', now()->toDateString())
                ->pluck('visits_count')
                ->map(fn ($count) => (int) $count)
                ->all();

            if (count($baseline) < 3) {
                continue;
            }

            $mean = array_sum($baseline) / count($baseline);
            $variance = array_sum(array_map(fn ($count) => ($count - $mean) ** 2, $baseline)) / count($baseline);
            $stddev = sqrt($variance);

            if ($stddev <= 0.0) {
                continue;
            }

            $today = (int) ($visits->aggregate((int) $shortUrlId, now()->startOfDay(), now())['visits_count'] ?? 0);
            $zScore = ($today - $mean) / $stddev;

            if (abs($zScore) >= $threshold) {
                $this->raiseAlert((int) $shortUrlId, $zScore, $today, $mean, $stddev, $threshold);
            }
        }

        return self::SUCCESS;
    }

    protected function raiseAlert(int $shortUrlId, float $zScore, int $today, float $mean, float $stddev, float $threshold): void
    {
        $alert = Alert::query()->create([
            'short_url_id' => $shortUrlId,
            'type' => $zScore > 0 ? 'visit_spike' : 'visit_drop',
            'severity' => abs($zScore) >= $threshold * 1.5 ? 'critical' : 'warning',
            'message' => sprintf('Visit count anomaly detected (z-score %.2f).', $zScore),
            'metrics' => ['today' => $today, 'baseline_mean' => $mean, 'baseline_stddev' => $stddev, 'z_score' => $zScore],
            'triggered_at' => now(),
            'created_at' => now(),
        ]);

        AlertTriggered::dispatch($alert);

        (new AnonymousNotifiable)
            ->route('mail', (array) config('short-url.notifications.mail_to', []))
            ->notify(new AlertNotification($alert));
    }
}
