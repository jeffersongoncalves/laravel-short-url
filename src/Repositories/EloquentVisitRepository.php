<?php

namespace JeffersonGoncalves\LaravelShortUrl\Repositories;

use DateTimeInterface;
use Illuminate\Support\Collection;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

class EloquentVisitRepository implements VisitRepository
{
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
        return $this->summarize(
            Visit::query()->where('short_url_id', $shortUrlId)->whereBetween('visited_at', [$from, $to])->get()
        );
    }

    public function aggregateMany(array $shortUrlIds, DateTimeInterface $from, DateTimeInterface $to): array
    {
        if ($shortUrlIds === []) {
            return $this->summarize(new Collection);
        }

        return $this->summarize(
            Visit::query()->whereIn('short_url_id', $shortUrlIds)->whereBetween('visited_at', [$from, $to])->get()
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
     * @param  Collection<int, Visit>  $visits
     * @return array<string, mixed>
     */
    protected function summarize(Collection $visits): array
    {
        return [
            'visits_count' => $visits->where('is_bot', false)->count(),
            'unique_visits_count' => $visits->where('is_bot', false)->unique('ip_hash')->count(),
            'bot_visits_count' => $visits->where('is_bot', true)->count(),
            'device_stats' => $this->counts($visits, 'device_type'),
            'browser_stats' => $this->counts($visits, 'browser'),
            'os_stats' => $this->counts($visits, 'operating_system'),
            'country_stats' => $this->counts($visits, 'country_code'),
            'city_stats' => $this->counts($visits, 'city'),
            'referer_stats' => $this->counts($visits, 'referer_host'),
            'referer_type_stats' => $this->counts($visits, 'referer_type'),
            'utm_source_stats' => $this->counts($visits, 'utm_source'),
            'utm_medium_stats' => $this->counts($visits, 'utm_medium'),
            'utm_campaign_stats' => $this->counts($visits, 'utm_campaign'),
            'language_stats' => $this->counts($visits, 'browser_language'),
            'variant_stats' => $this->counts($visits, 'selected_variant'),
            'hourly_stats' => $visits->groupBy(fn (Visit $visit) => $visit->visited_at->format('G'))
                ->map(fn ($group) => $group->count())
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Visit>  $visits
     * @return array<string, int>
     */
    protected function counts(Collection $visits, string $column): array
    {
        return $visits
            ->filter(fn (Visit $visit) => filled($visit->{$column}))
            ->countBy($column)
            ->all();
    }
}
