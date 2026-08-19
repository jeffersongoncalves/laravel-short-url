<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Notifications\AlertNotification;

function makeAlert(): Alert
{
    // Persisted, not just built in memory: AlertNotification implements
    // ShouldQueue, and even on the sync queue connection the job still
    // round-trips through SerializesModels, which re-fetches the model by
    // id — a row has to actually exist for that lookup to succeed.
    return Alert::query()->create([
        'short_url_id' => ShortUrl::factory()->create()->id,
        'type' => 'visit_spike',
        'severity' => 'warning',
        'message' => 'Visits spiked.',
        'triggered_at' => now(),
        'created_at' => now(),
    ]);
}

it('selects no channels when nothing is configured', function () {
    config([
        'short-url.notifications.mail_to' => [],
        'short-url.notifications.database_enabled' => false,
        'short-url.notifications.broadcast_enabled' => false,
        'short-url.notifications.telegram_bot_token' => null,
    ]);

    $notification = new AlertNotification(makeAlert());

    expect($notification->via(new AnonymousNotifiable))->toBe([]);
});

it('posts to telegram when bot token and chat id are configured', function () {
    config(['short-url.notifications.telegram_bot_token' => 'tok', 'short-url.notifications.telegram_chat_id' => '123']);
    Http::fake(['*api.telegram.org*' => Http::response('ok')]);

    (new AnonymousNotifiable)->notify(new AlertNotification(makeAlert()));

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org/bottok/sendMessage'));
});
