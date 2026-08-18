<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;

class SyncCountersCommand extends Command
{
    protected $signature = 'short-url:sync-counters';

    protected $description = 'Flush Redis-buffered visit counters into the short_urls table.';

    public function handle(CounterBuffer $buffer): int
    {
        $buffer->flush();

        return self::SUCCESS;
    }
}
