<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Models\ApiKey;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $token = Str::random(48);

        return [
            'name' => fake()->words(2, true),
            'key_prefix' => substr($token, 0, 8),
            'key_hash' => hash('sha256', $token),
            'abilities' => ['*'],
        ];
    }
}
