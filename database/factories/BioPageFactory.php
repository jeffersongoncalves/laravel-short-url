<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\BioPage;

/**
 * @extends Factory<BioPage>
 */
class BioPageFactory extends Factory
{
    protected $model = BioPage::class;

    public function definition(): array
    {
        return [
            'handle' => fake()->unique()->userName(),
            'title' => fake()->name(),
            'is_published' => true,
        ];
    }
}
