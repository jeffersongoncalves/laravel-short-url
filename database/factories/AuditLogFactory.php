<?php

namespace JeffersonGoncalves\LaravelShortUrl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelShortUrl\Models\AuditLog;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'event' => 'updated',
        ];
    }
}
