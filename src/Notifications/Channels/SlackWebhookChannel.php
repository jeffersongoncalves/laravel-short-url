<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Throwable;

class SlackWebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $url = config('short-url.notifications.slack_webhook_url');

        if (! $url || ! method_exists($notification, 'toSlack')) {
            return;
        }

        try {
            Http::timeout(3)->post($url, ['text' => $notification->toSlack($notifiable)]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
