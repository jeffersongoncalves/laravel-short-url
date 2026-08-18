<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\Pixel;

/**
 * @extends Factory<Pixel>
 */
class PixelFactory extends Factory
{
    protected $model = Pixel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'provider_key' => 'meta_pixel',
            'config' => ['pixel_id' => (string) fake()->numberBetween(100000, 999999)],
        ];
    }
}
