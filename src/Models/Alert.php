<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $short_url_id
 * @property string $type
 * @property string $severity
 * @property string $message
 * @property array<string, mixed>|null $metrics
 */
class Alert extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'alerts';
    }
}
