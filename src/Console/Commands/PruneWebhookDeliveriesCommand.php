<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

class PruneWebhookDeliveriesCommand extends Command
{
    protected $signature = 'short-url:prune-webhook-deliveries';

    protected $description = 'Delete webhook delivery logs past the configured retention window.';

    public function handle(): int
    {
        $days = (int) config('short-url.webhooks.delivery_retention_days', 30);

        WebhookDelivery::query()->where('created_at', '<', now()->subDays($days))->delete();

        return self::SUCCESS;
    }
}
