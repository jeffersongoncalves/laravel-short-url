<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event' => 'link.visited',
            'payload' => ['example' => true],
            'attempt' => 1,
            'succeeded' => true,
            'response_status' => 200,
        ];
    }
}
