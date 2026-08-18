<?php

namespace JeffersonGoncalves\LaravelShortUrl\Webhooks;

use JeffersonGoncalves\LaravelShortUrl\Contracts\WebhookDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;

class EloquentWebhookDispatcher implements WebhookDispatcher
{
    public function dispatch(string $event, array $payload, ?ShortUrl $shortUrl = null): void
    {
        $webhooks = Webhook::query()
            ->where('is_active', true)
            ->whereNull('disabled_at')
            ->where(function ($query) use ($shortUrl): void {
                $query->whereNull('short_url_id');

                if ($shortUrl) {
                    $query->orWhere('short_url_id', $shortUrl->id);
                }
            })
            ->get()
            ->filter(fn (Webhook $webhook) => $webhook->handlesEvent($event));

        foreach ($webhooks as $webhook) {
            SendWebhookJob::dispatch($webhook->id, $event, $payload);
        }
    }
}
