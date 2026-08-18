<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;
use Throwable;

/**
 * One delivery attempt. On failure, re-dispatches itself with the next
 * retry delay from short-url.webhooks.retry_seconds until attempts are
 * exhausted, at which point the webhook's failure_count accrues toward
 * short-url.webhooks.max_failures (auto-disable).
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $webhookId,
        public string $event,
        public array $payload,
        public int $attempt = 1,
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::query()->find($this->webhookId);

        if (! $webhook || ! $webhook->is_active || $webhook->disabled_at) {
            return;
        }

        $timestamp = (string) now()->timestamp;
        $body = json_encode(['event' => $this->event, 'data' => $this->payload, 'timestamp' => $timestamp]);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $webhook->secret);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-ShortUrl-Signature' => $signature,
                'X-ShortUrl-Timestamp' => $timestamp,
            ])->timeout(5)->withBody($body, 'application/json')->post($webhook->url);

            $this->recordDelivery($response->successful(), $response->status(), substr($response->body(), 0, 2000));

            if ($response->successful()) {
                $webhook->update(['failure_count' => 0]);

                return;
            }
        } catch (Throwable $e) {
            $this->recordDelivery(false, null, $e->getMessage());
        }

        $this->handleFailure($webhook);
    }

    protected function recordDelivery(bool $succeeded, ?int $status, ?string $responseBody): void
    {
        WebhookDelivery::query()->create([
            'webhook_id' => $this->webhookId,
            'event' => $this->event,
            'payload' => $this->payload,
            'attempt' => $this->attempt,
            'succeeded' => $succeeded,
            'response_status' => $status,
            'response_body' => $responseBody,
            'delivered_at' => now(),
            'created_at' => now(),
        ]);
    }

    protected function handleFailure(Webhook $webhook): void
    {
        $retrySeconds = config('short-url.webhooks.retry_seconds', [10, 60, 300]);

        if ($this->attempt <= count($retrySeconds)) {
            static::dispatch($this->webhookId, $this->event, $this->payload, $this->attempt + 1)
                ->delay($retrySeconds[$this->attempt - 1]);
        }

        $failureCount = $webhook->failure_count + 1;
        $maxFailures = (int) config('short-url.webhooks.max_failures', 20);

        $webhook->update([
            'failure_count' => $failureCount,
            'disabled_at' => $failureCount >= $maxFailures ? now() : $webhook->disabled_at,
        ]);
    }
}
