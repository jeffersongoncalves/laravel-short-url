<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Throwable;

class TeamsWebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $url = config('short-url.notifications.teams_webhook_url');

        if (! $url || ! method_exists($notification, 'toTeams')) {
            return;
        }

        try {
            Http::timeout(3)->post($url, ['text' => $notification->toTeams($notifiable)]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
