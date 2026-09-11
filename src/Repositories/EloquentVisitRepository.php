<?php

namespace JeffersonGoncalves\LaravelShortUrl\Repositories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

class EloquentVisitRepository implements VisitRepository
{
    /**
     * @var array<string, string>
     */
    protected const DIMENSION_COLUMNS = [
        'device_stats' => 'device_type',
        'browser_stats' => 'browser',
        'os_stats' => 'operating_system',
        'country_stats' => 'country_code',
        'city_stats' => 'city',
        'referer_stats' => 'referer_host',
        'referer_type_stats' => 'referer_type',
        'utm_source_stats' => 'utm_source',
        'utm_medium_stats' => 'utm_medium',
        'utm_campaign_stats' => 'utm_campaign',
        'language_stats' => 'browser_language',
        'variant_stats' => 'selected_variant',
    ];

    public function store(array $attributes): void
    {
        Visit::query()->create($attributes);
    }

    public function query(int $shortUrlId, array $filters = []): array
    {
        $query = Visit::query()->where('short_url_id', $shortUrlId);

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get()->map(fn (Visit $visit) => $visit->toArray())->all();
    }

    public function aggregate(int $shortUrlId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->runAggregate(
            Visit::query()->where('short_url_id', $shortUrlId)->whereBetween('visited_at', [$from, $to])
        );
    }

    public function aggregateMany(?array $shortUrlIds, DateTimeInterface $from, DateTimeInterface $to): array
    {
        if ($shortUrlIds === []) {
            return $this->emptyResult();
        }

        return $this->runAggregate(
            Visit::query()
                ->when($shortUrlIds !== null, fn ($query) => $query->whereIn('short_url_id', $shortUrlIds))
                ->whereBetween('visited_at', [$from, $to])
        );
    }

    public function prune(DateTimeInterface $before, int|string|null $tenantId = null): int
    {
        return Visit::query()
            ->where('visited_at', '<', $before)
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->delete();
    }

    /**
     * Pushes each dimension breakdown down to a GROUP BY query instead of
     * pulling every matching row into PHP — see issue #16.
     *
     * @param  Builder<Visit>  $query
     * @return array<string, mixed>
     */
    protected function runAggregate(Builder $query): array
    {
        $result = [
            'visits_count' => (clone $query)->where('is_bot', false)->count(),
            'unique_visits_count' => (clone $query)->where('is_bot', false)->distinct()->count('ip_hash'),
            'bot_visits_count' => (clone $query)->where('is_bot', true)->count(),
        ];

        foreach (self::DIMENSION_COLUMNS as $key => $column) {
            $result[$key] = $this->dimensionCounts(clone $query, $column);
        }

        $result['hourly_stats'] = $this->hourlyCounts(clone $query);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(): array
    {
        $result = [
            'visits_count' => 0,
            'unique_visits_count' => 0,
            'bot_visits_count' => 0,
        ];

        foreach (self::DIMENSION_COLUMNS as $key => $column) {
            $result[$key] = [];
        }

        $result['hourly_stats'] = [];

        return $result;
    }

    /**
     * @param  Builder<Visit>  $query
     * @return array<string, int>
     */
    protected function dimensionCounts(Builder $query, string $column): array
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->selectRaw("{$column} as label, count(*) as aggregate")
            ->pluck('aggregate', 'label')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @param  Builder<Visit>  $query
     * @return array<int, int>
     */
    protected function hourlyCounts(Builder $query): array
    {
        $hour = $this->hourExpression();

        return $query
            ->selectRaw("{$hour} as hour, count(*) as aggregate")
            ->groupBy(DB::raw($hour))
            ->pluck('aggregate', 'hour')
            ->mapWithKeys(fn ($count, $hour) => [(int) $hour => (int) $count])
            ->all();
    }

    protected function hourExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', visited_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(HOUR FROM visited_at)::int',
            default => 'HOUR(visited_at)',
        };
    }
}
