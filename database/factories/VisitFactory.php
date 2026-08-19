<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'visited_at' => now(),
            'ip_hash' => bin2hex(random_bytes(8)),
            'country_code' => fake()->countryCode(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'is_bot' => false,
            'is_vpn' => false,
            'is_proxy' => false,
            'is_tor' => false,
            'is_datacenter' => false,
        ];
    }
}
