<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Notifications\ScheduledReportNotification;

it('does nothing when scheduled reports are disabled', function () {
    Notification::fake();
    config(['short-url.notifications.scheduled_reports_enabled' => false, 'short-url.notifications.mail_to' => ['ops@example.com']]);

    $this->artisan('short-url:send-scheduled-reports')->assertExitCode(0);

    Notification::assertNothingSent();
});

it('does nothing when there are no recipients', function () {
    Notification::fake();
    config(['short-url.notifications.scheduled_reports_enabled' => true, 'short-url.notifications.mail_to' => []]);

    $this->artisan('short-url:send-scheduled-reports')->assertExitCode(0);

    Notification::assertNothingSent();
});

it('sends a top-links report to the configured recipients', function () {
    Notification::fake();
    config(['short-url.notifications.scheduled_reports_enabled' => true, 'short-url.notifications.mail_to' => ['ops@example.com']]);
    ShortUrl::factory()->create(['total_visits' => 42]);

    $this->artisan('short-url:send-scheduled-reports')->assertExitCode(0);

    Notification::assertSentOnDemand(ScheduledReportNotification::class);
});
