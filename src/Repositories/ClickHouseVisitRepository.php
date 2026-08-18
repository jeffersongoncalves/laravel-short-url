<?php

namespace JeffersonGoncalves\LaravelShortUrl\Repositories;

use DateTimeInterface;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use RuntimeException;

/**
 * Talks to ClickHouse over its native HTTP interface — no client library
 * dependency, just SQL over POST with JSONEachRow in/out. Same contract as
 * EloquentVisitRepository, so StatsAggregator and every other consumer
 * stays driver-agnostic. Selected via short-url.tracking.driver=clickhouse.
 */
class ClickHouseVisitRepository implements VisitRepository
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
        $this->execute($this->insertSql($attributes));
    }

    public function query(int $shortUrlId, array $filters = []): array
    {
        $where = ['short_url_id = '.$shortUrlId];

        foreach ($filters as $column => $value) {
            $where[] = $this->quoteIdentifier($column).' = '.$this->quoteValue($value);
        }

        $sql = 'SELECT * FROM '.$this->table().' WHERE '.implode(' AND ', $where).' FORMAT JSONEachRow';

        return $this->executeAndDecode($sql);
    }

    public function aggregate(int $shortUrlId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $table = $this->table();
        $range = sprintf(
            "short_url_id = %d AND visited_at BETWEEN '%s' AND '%s'",
            $shortUrlId,
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        );

        $totals = $this->executeAndDecode(
            "SELECT
                countIf(is_bot = 0) AS visits_count,
                uniqIf(ip_hash, is_bot = 0) AS unique_visits_count,
                countIf(is_qr_scan = 1) AS qr_visits_count,
                countIf(is_bot = 1) AS bot_visits_count
            FROM {$table} WHERE {$range} FORMAT JSONEachRow"
        )[0] ?? ['visits_count' => 0, 'unique_visits_count' => 0, 'qr_visits_count' => 0, 'bot_visits_count' => 0];

        $result = [
            'visits_count' => (int) $totals['visits_count'],
            'unique_visits_count' => (int) $totals['unique_visits_count'],
            'qr_visits_count' => (int) $totals['qr_visits_count'],
            'bot_visits_count' => (int) $totals['bot_visits_count'],
        ];

        foreach (self::DIMENSION_COLUMNS as $key => $column) {
            $result[$key] = $this->dimensionCounts($table, $range, $column);
        }

        $result['hourly_stats'] = $this->hourlyCounts($table, $range);

        return $result;
    }

    public function prune(DateTimeInterface $before, int|string|null $tenantId = null): int
    {
        $table = $this->table();
        $condition = "visited_at < '".$before->format('Y-m-d H:i:s')."'";

        if ($tenantId !== null) {
            $condition .= ' AND tenant_id = '.$this->quoteValue($tenantId);
        }

        $countRows = $this->executeAndDecode(
            "SELECT count() AS c FROM {$table} WHERE {$condition} FORMAT JSONEachRow"
        );
        $count = (int) ($countRows[0]['c'] ?? 0);

        // ALTER TABLE ... DELETE is ClickHouse's mutation-based delete —
        // async by design, unlike a normal SQL DELETE.
        $this->execute("ALTER TABLE {$table} DELETE WHERE {$condition}");

        return $count;
    }

    /**
     * @return array<string, int>
     */
    protected function dimensionCounts(string $table, string $range, string $column): array
    {
        $rows = $this->executeAndDecode(
            "SELECT {$column} AS label, count() AS c FROM {$table}
            WHERE {$range} AND {$column} != '' GROUP BY {$column} FORMAT JSONEachRow"
        );

        return collect($rows)->pluck('c', 'label')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @return array<int, int>
     */
    protected function hourlyCounts(string $table, string $range): array
    {
        $rows = $this->executeAndDecode(
            "SELECT toHour(visited_at) AS hour, count() AS c FROM {$table}
            WHERE {$range} GROUP BY hour FORMAT JSONEachRow"
        );

        return collect($rows)->mapWithKeys(fn ($row) => [(int) $row['hour'] => (int) $row['c']])->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function insertSql(array $attributes): string
    {
        $columns = array_keys($attributes);
        $values = array_map(fn ($value) => $this->quoteValue($value), array_values($attributes));

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table(),
            implode(', ', array_map($this->quoteIdentifier(...), $columns)),
            implode(', ', $values)
        );
    }

    protected function quoteIdentifier(string $column): string
    {
        return '`'.str_replace('`', '', $column).'`';
    }

    protected function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return "'".$value->format('Y-m-d H:i:s')."'";
        }

        return "'".str_replace("'", "\\'", (string) $value)."'";
    }

    protected function table(): string
    {
        return config('short-url.table_prefix', 'short_url_').'visits';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function executeAndDecode(string $sql): array
    {
        $body = $this->execute($sql);

        if (trim($body) === '') {
            return [];
        }

        return array_map(
            fn (string $line) => json_decode($line, true) ?? [],
            array_filter(explode("\n", trim($body)))
        );
    }

    protected function execute(string $sql): string
    {
        $config = config('short-url.tracking.clickhouse', []);
        $host = $config['host'] ?? null;

        if (! $host) {
            throw new RuntimeException('short-url.tracking.clickhouse.host is not configured.');
        }

        $response = Http::withBasicAuth($config['username'] ?? 'default', $config['password'] ?? '')
            ->timeout(5)
            ->withBody($sql, 'text/plain')
            ->post(sprintf(
                'http://%s:%d/?database=%s',
                $host,
                $config['port'] ?? 8123,
                $config['database'] ?? 'default'
            ));

        if (! $response->successful()) {
            throw new RuntimeException('ClickHouse query failed: '.$response->body());
        }

        return $response->body();
    }
}
