<?php

use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

it('deletes deliveries older than the retention window and keeps recent ones', function () {
    config(['short-url.webhooks.delivery_retention_days' => 30]);
    $webhook = Webhook::factory()->create();

    WebhookDelivery::query()->create([
        'webhook_id' => $webhook->id, 'event' => 'link.visited', 'payload' => [],
        'attempt' => 1, 'succeeded' => true, 'created_at' => now()->subDays(60),
    ]);
    WebhookDelivery::query()->create([
        'webhook_id' => $webhook->id, 'event' => 'link.visited', 'payload' => [],
        'attempt' => 1, 'succeeded' => true, 'created_at' => now()->subDays(5),
    ]);

    $this->artisan('short-url:prune-webhook-deliveries')->assertExitCode(0);

    expect(WebhookDelivery::query()->count())->toBe(1);
});
