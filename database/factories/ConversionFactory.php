<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    protected $model = Conversion::class;

    public function definition(): array
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'event_name' => 'purchase',
            'occurred_at' => now(),
        ];
    }
}
