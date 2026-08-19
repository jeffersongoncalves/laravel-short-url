<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'type' => 'visit_spike',
            'severity' => 'warning',
            'message' => fake()->sentence(),
            'triggered_at' => now(),
        ];
    }
}
