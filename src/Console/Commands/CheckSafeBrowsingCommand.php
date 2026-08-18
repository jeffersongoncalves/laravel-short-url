<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelShortUrl\Jobs\CheckSafeBrowsingJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

class CheckSafeBrowsingCommand extends Command
{
    protected $signature = 'short-url:check-safe-browsing {--stale-days=7 : Re-check urls last checked more than this many days ago}';

    protected $description = 'Re-scan enabled short urls (and their variants/rules) against Safe Browsing.';

    public function handle(): int
    {
        if (! config('short-url.security.safe_browsing.enabled', false)) {
            return self::SUCCESS;
        }

        $staleBefore = now()->subDays((int) $this->option('stale-days'));

        ShortUrl::query()
            ->enabled()
            ->where(function ($query) use ($staleBefore) {
                $query->whereNull('safe_browsing_checked_at')
                    ->orWhere('safe_browsing_checked_at', '<', $staleBefore);
            })
            ->pluck('id')
            ->each(fn (int $id) => CheckSafeBrowsingJob::dispatch($id));

        return self::SUCCESS;
    }
}
