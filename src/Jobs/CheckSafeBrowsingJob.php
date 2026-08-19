<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Security\DestinationUrlCollector;
use Throwable;

class CheckSafeBrowsingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $shortUrlId) {}

    public function handle(SafeBrowsingChecker $checker): void
    {
        try {
            $shortUrl = ShortUrl::query()->find($this->shortUrlId);

            if (! $shortUrl) {
                return;
            }

            $status = 'safe';
            $unsafeUrl = null;
            $threats = [];

            foreach (DestinationUrlCollector::collect($shortUrl) as $url) {
                $result = $checker->check($url);

                if ($result->status === 'unsafe') {
                    $status = 'unsafe';
                    $unsafeUrl = $url;
                    $threats = $result->threats;
                    break;
                }

                if ($result->status === 'unknown') {
                    $status = 'unknown';
                }
            }

            $shortUrl->forceFill([
                'safe_browsing_status' => $status,
                'safe_browsing_checked_at' => now(),
            ])->saveQuietly();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
