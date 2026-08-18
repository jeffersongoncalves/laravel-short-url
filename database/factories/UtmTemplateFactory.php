<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;

/**
 * @extends Factory<UtmTemplate>
 */
class UtmTemplateFactory extends Factory
{
    protected $model = UtmTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => fake()->word(),
        ];
    }
}
