<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * @extends Factory<ShortUrl>
 */
class ShortUrlFactory extends Factory
{
    protected $model = ShortUrl::class;

    public function definition(): array
    {
        return [
            'url_key' => Str::random(7),
            'destination_url' => fake()->url(),
            'destination_type' => 'single',
            'is_enabled' => true,
            'redirect_status_code' => 302,
            'forward_query_params' => true,
            'total_visits' => 0,
        ];
    }
}
