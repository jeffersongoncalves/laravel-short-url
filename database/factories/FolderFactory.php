<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\Folder;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }
}
