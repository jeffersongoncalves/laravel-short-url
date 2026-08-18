<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Notifications\ScheduledReportNotification;

class SendScheduledReportsCommand extends Command
{
    protected $signature = 'short-url:send-scheduled-reports';

    protected $description = 'Email a weekly top-links summary to the configured report recipients.';

    public function handle(): int
    {
        $recipients = (array) config('short-url.notifications.mail_to', []);

        if ($recipients === [] || ! config('short-url.notifications.scheduled_reports_enabled', false)) {
            return self::SUCCESS;
        }

        $topLinks = ShortUrl::query()
            ->enabled()
            ->orderByDesc('total_visits')
            ->limit(10)
            ->get(['title', 'url_key', 'total_visits'])
            ->map(fn (ShortUrl $shortUrl) => [
                'title' => $shortUrl->title ?: $shortUrl->url_key,
                'url_key' => $shortUrl->url_key,
                'total_visits' => $shortUrl->total_visits,
            ])
            ->all();

        (new AnonymousNotifiable)
            ->route('mail', $recipients)
            ->notify(new ScheduledReportNotification($topLinks, now()->format('Y-m-d')));

        return self::SUCCESS;
    }
}
