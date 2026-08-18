<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\BioLink;

/**
 * @extends Factory<BioLink>
 */
class BioLinkFactory extends Factory
{
    protected $model = BioLink::class;

    public function definition(): array
    {
        return [
            'type' => 'link',
            'label' => fake()->words(2, true),
            'content' => ['url' => fake()->url()],
            'position' => 0,
            'is_enabled' => true,
            'click_count' => 0,
        ];
    }
}
