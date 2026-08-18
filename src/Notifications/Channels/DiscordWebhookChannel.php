<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Throwable;

class DiscordWebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $url = config('short-url.notifications.discord_webhook_url');

        if (! $url || ! method_exists($notification, 'toDiscord')) {
            return;
        }

        try {
            Http::timeout(3)->post($url, ['content' => $notification->toDiscord($notifiable)]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
