<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Notifications\Channels\TelegramChannel;

/**
 * Channels are selected from config rather than per-notifiable routing —
 * this package has no user model of its own, so "who gets notified" is an
 * operator-level setting (short-url.notifications.*), not a per-user
 * preference.
 */
class AlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Alert $alert) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = [];

        if (config('short-url.notifications.mail_to')) {
            $channels[] = 'mail';
        }

        if (config('short-url.notifications.database_enabled', false)) {
            $channels[] = 'database';
        }

        if (config('short-url.notifications.broadcast_enabled', false)) {
            $channels[] = 'broadcast';
        }

        if (config('short-url.notifications.telegram_bot_token') && config('short-url.notifications.telegram_chat_id')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Short URL alert: {$this->alert->type}")
            ->line($this->alert->message);
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toTelegram(mixed $notifiable): string
    {
        return $this->alert->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'short_url_id' => $this->alert->short_url_id,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'message' => $this->alert->message,
        ];
    }
}
