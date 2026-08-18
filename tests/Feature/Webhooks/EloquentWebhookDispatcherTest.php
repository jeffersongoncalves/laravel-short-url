<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Webhooks\EloquentWebhookDispatcher;

it('dispatches to a global webhook subscribed to the event', function () {
    Bus::fake();
    $webhook = Webhook::factory()->create(['short_url_id' => null, 'events' => ['link.visited']]);

    (new EloquentWebhookDispatcher)->dispatch('link.visited', ['x' => 1]);

    Bus::assertDispatched(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->webhookId === $webhook->id);
});

it('does not dispatch to a global webhook not subscribed to the event', function () {
    Bus::fake();
    Webhook::factory()->create(['short_url_id' => null, 'events' => ['link.created']]);

    (new EloquentWebhookDispatcher)->dispatch('link.visited', ['x' => 1]);

    Bus::assertNotDispatched(SendWebhookJob::class);
});

it('dispatches to a webhook scoped to the given short url', function () {
    Bus::fake();
    $shortUrl = ShortUrl::factory()->create();
    $webhook = Webhook::factory()->create(['short_url_id' => $shortUrl->id, 'events' => ['link.visited']]);

    (new EloquentWebhookDispatcher)->dispatch('link.visited', ['x' => 1], $shortUrl);

    Bus::assertDispatched(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->webhookId === $webhook->id);
});

it('does not dispatch to a webhook scoped to a different short url', function () {
    Bus::fake();
    $shortUrl = ShortUrl::factory()->create();
    $otherShortUrl = ShortUrl::factory()->create();
    Webhook::factory()->create(['short_url_id' => $otherShortUrl->id, 'events' => ['*']]);

    (new EloquentWebhookDispatcher)->dispatch('link.visited', ['x' => 1], $shortUrl);

    Bus::assertNotDispatched(SendWebhookJob::class);
});

it('honors a wildcard event subscription', function () {
    Bus::fake();
    Webhook::factory()->create(['short_url_id' => null, 'events' => ['*']]);

    (new EloquentWebhookDispatcher)->dispatch('link.deleted', []);

    Bus::assertDispatched(SendWebhookJob::class);
});

it('never dispatches to a disabled webhook', function () {
    Bus::fake();
    Webhook::factory()->create(['short_url_id' => null, 'events' => ['*'], 'disabled_at' => now()]);

    (new EloquentWebhookDispatcher)->dispatch('link.deleted', []);

    Bus::assertNotDispatched(SendWebhookJob::class);
});
