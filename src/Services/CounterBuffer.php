<?php

namespace JeffersonGoncalves\LaravelShortUrl\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use JeffersonGoncalves\LaravelShortUrl\Jobs\IncrementVisitJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use Throwable;

/**
 * Buffers per-short-url visit counters in Redis so a burst of redirects
 * doesn't hammer the short_urls row with row-locking UPDATE statements.
 * short-url:sync-counters periodically flushes the buffer into the database.
 */
class CounterBuffer
{
    /**
     * @param  array<string, int>  $counters
     */
    public function increment(int $shortUrlId, array $counters): void
    {
        $counters = array_filter($counters, fn (int $amount) => $amount !== 0);

        if ($counters === []) {
            return;
        }

        if (! $this->bufferingEnabled()) {
            IncrementVisitJob::dispatch($shortUrlId, $counters);

            return;
        }

        try {
            $redis = Redis::connection($this->connectionName());

            foreach ($counters as $column => $amount) {
                $redis->hincrby($this->hashKey($shortUrlId), $column, $amount);
            }

            $redis->sadd($this->pendingSetKey(), $shortUrlId);
        } catch (Throwable $e) {
            report($e);

            IncrementVisitJob::dispatch($shortUrlId, $counters);
        }
    }

    /**
     * Flushes every buffered short url's counters into the database.
     * Runs from short-url:sync-counters. A short url whose DB update fails
     * keeps its buffered counters in Redis so the next run retries it —
     * counters are only cleared after a confirmed successful write.
     */
    public function flush(): void
    {
        if (! $this->bufferingEnabled()) {
            return;
        }

        try {
            $redis = Redis::connection($this->connectionName());
            $ids = $redis->smembers($this->pendingSetKey());
        } catch (Throwable $e) {
            report($e);

            return;
        }

        foreach ($ids as $id) {
            $this->flushOne($redis, (int) $id);
        }
    }

    protected function flushOne(mixed $redis, int $shortUrlId): void
    {
        $hashKey = $this->hashKey($shortUrlId);

        try {
            $counters = $redis->hgetall($hashKey);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        if ($counters === []) {
            $redis->srem($this->pendingSetKey(), $shortUrlId);

            return;
        }

        try {
            DB::transaction(function () use ($shortUrlId, $counters): void {
                self::applyCounters($shortUrlId, array_map('intval', $counters));
            });

            $redis->del($hashKey);
            $redis->srem($this->pendingSetKey(), $shortUrlId);
        } catch (Throwable $e) {
            // Deliberately do not clear the Redis buffer: counters stay put
            // for the next sync-counters run instead of being lost.
            report($e);
        }
    }

    /**
     * @param  array<string, int>  $counters
     */
    public static function applyCounters(int $shortUrlId, array $counters): void
    {
        $updates = [];

        foreach ($counters as $column => $amount) {
            if ($amount !== 0) {
                $updates[$column] = DB::raw("{$column} + ({$amount})");
            }
        }

        if ($updates === []) {
            return;
        }

        ShortUrl::query()->where('id', $shortUrlId)->update($updates);
    }

    protected function bufferingEnabled(): bool
    {
        return (bool) config('short-url.tracking.counter_buffering', false);
    }

    protected function connectionName(): string
    {
        return (string) config('short-url.tracking.redis_connection', 'default');
    }

    protected function hashKey(int $shortUrlId): string
    {
        return config('short-url.cache.prefix', 'short_url').":counters:{$shortUrlId}";
    }

    protected function pendingSetKey(): string
    {
        return config('short-url.cache.prefix', 'short_url').':counters:pending';
    }
}
