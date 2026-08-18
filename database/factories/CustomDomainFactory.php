<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

/**
 * @extends Factory<CustomDomain>
 */
class CustomDomainFactory extends Factory
{
    protected $model = CustomDomain::class;

    public function definition(): array
    {
        return [
            'domain' => Str::lower(fake()->unique()->domainWord()).'.test',
            'is_wildcard' => false,
            'verification_token' => 'short-url-verify-'.Str::random(32),
            'is_verified' => false,
            'failure_count' => 0,
        ];
    }

    public function verified(): static
    {
        return $this->state(['is_verified' => true, 'verified_at' => now()]);
    }
}
