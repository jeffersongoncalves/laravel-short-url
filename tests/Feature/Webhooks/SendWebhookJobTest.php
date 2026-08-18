<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

it('signs the payload with an hmac signature header', function () {
    Http::fake(['*' => Http::response('ok', 200)]);
    $webhook = Webhook::factory()->create(['url' => 'https://consumer.test/hook']);

    (new SendWebhookJob($webhook->id, 'link.visited', ['x' => 1]))->handle();

    Http::assertSent(function (Request $request) use ($webhook) {
        $timestamp = $request->header('X-ShortUrl-Timestamp')[0] ?? null;
        $signature = $request->header('X-ShortUrl-Signature')[0] ?? null;
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->body(), $webhook->secret);

        return $signature === $expected;
    });
});

it('records a successful delivery and resets the failure count', function () {
    Http::fake(['*' => Http::response('ok', 200)]);
    $webhook = Webhook::factory()->create(['failure_count' => 2]);

    (new SendWebhookJob($webhook->id, 'link.visited', ['x' => 1]))->handle();

    expect(WebhookDelivery::query()->where('webhook_id', $webhook->id)->where('succeeded', true)->exists())->toBeTrue()
        ->and($webhook->refresh()->failure_count)->toBe(0);
});

it('records a failed delivery and schedules a retry', function () {
    Bus::fake();
    Http::fake(['*' => Http::response('error', 500)]);
    $webhook = Webhook::factory()->create();

    (new SendWebhookJob($webhook->id, 'link.visited', ['x' => 1]))->handle();

    expect(WebhookDelivery::query()->where('webhook_id', $webhook->id)->where('succeeded', false)->exists())->toBeTrue()
        ->and($webhook->refresh()->failure_count)->toBe(1);

    Bus::assertDispatched(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->attempt === 2);
});

it('disables the webhook after reaching max_failures', function () {
    config(['short-url.webhooks.max_failures' => 1, 'short-url.webhooks.retry_seconds' => []]);
    Http::fake(['*' => Http::response('error', 500)]);
    $webhook = Webhook::factory()->create();

    (new SendWebhookJob($webhook->id, 'link.visited', ['x' => 1]))->handle();

    expect($webhook->refresh()->disabled_at)->not->toBeNull();
});

it('does nothing for a disabled webhook', function () {
    Http::fake();
    $webhook = Webhook::factory()->create(['disabled_at' => now()]);

    (new SendWebhookJob($webhook->id, 'link.visited', ['x' => 1]))->handle();

    Http::assertNothingSent();
});
