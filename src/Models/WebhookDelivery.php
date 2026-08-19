<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\WebhookDeliveryFactory;

/**
 * @property int $id
 * @property int $webhook_id
 * @property string $event
 * @property array<string, mixed> $payload
 * @property int $attempt
 * @property bool $succeeded
 * @property int|null $response_status
 * @property string|null $response_body
 */
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'succeeded' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'webhook_deliveries';
    }

    protected static function newFactory(): WebhookDeliveryFactory
    {
        return WebhookDeliveryFactory::new();
    }
}
