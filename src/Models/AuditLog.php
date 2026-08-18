<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $short_url_id
 * @property int|null $user_id
 * @property string $event
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'audit_logs';
    }
}
