<?php

namespace JeffersonGoncalves\LaravelShortUrl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduledReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{title: string, url_key: string, total_visits: int}>  $topLinks
     */
    public function __construct(public array $topLinks, public string $period) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject("Short URL report — {$this->period}");

        if ($this->topLinks === []) {
            return $mail->line('No visits recorded in this period.');
        }

        $mail->line("Top links for {$this->period}:");

        foreach ($this->topLinks as $link) {
            $mail->line("- {$link['title']} ({$link['url_key']}): {$link['total_visits']} visits");
        }

        return $mail;
    }
}
