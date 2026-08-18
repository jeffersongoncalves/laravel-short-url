<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $token = config('short-url.notifications.telegram_bot_token');
        $chatId = config('short-url.notifications.telegram_chat_id');

        if (! $token || ! $chatId || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        try {
            Http::timeout(3)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $notification->toTelegram($notifiable),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
