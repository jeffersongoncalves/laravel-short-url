<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;

/**
 * Fallback path for CounterBuffer when Redis buffering is disabled or
 * unreachable: applies the counter deltas directly to the short_urls row.
 */
class IncrementVisitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, int>  $counters
     */
    public function __construct(
        public int $shortUrlId,
        public array $counters,
    ) {}

    public function handle(): void
    {
        CounterBuffer::applyCounters($this->shortUrlId, $this->counters);
    }
}
