<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('lists webhooks', function () {
    Webhook::factory()->count(2)->create();

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/webhooks')
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});

it('creates a webhook', function () {
    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/webhooks', [
            'url' => 'https://consumer.test/hook',
            'events' => ['link.visited'],
        ])
        ->assertCreated();

    expect(Webhook::query()->where('url', 'https://consumer.test/hook')->exists())->toBeTrue();
});

it('deletes a webhook', function () {
    $webhook = Webhook::factory()->create();

    $this->withHeaders(apiHeaders(['links:write']))
        ->deleteJson("/api/short-url/v1/webhooks/{$webhook->id}")
        ->assertStatus(204);

    expect(Webhook::query()->find($webhook->id))->toBeNull();
});

it('replays a delivery', function () {
    Bus::fake();
    $webhook = Webhook::factory()->create();
    $delivery = WebhookDelivery::query()->create([
        'webhook_id' => $webhook->id,
        'event' => 'link.visited',
        'payload' => ['x' => 1],
        'attempt' => 1,
        'succeeded' => false,
        'created_at' => now(),
    ]);

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson("/api/short-url/v1/webhook-deliveries/{$delivery->id}/replay")
        ->assertStatus(202);

    Bus::assertDispatched(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->webhookId === $webhook->id);
});
