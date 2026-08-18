<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $short_url_id
 * @property string $url
 * @property string $secret
 * @property array<int, string> $events
 * @property bool $is_active
 * @property int $failure_count
 * @property Carbon|null $disabled_at
 */
class Webhook extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'failure_count' => 'integer',
            'disabled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $webhook): void {
            $webhook->secret ??= Str::random(40);
        });
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'webhooks';
    }

    public function handlesEvent(string $event): bool
    {
        return in_array('*', $this->events, true) || in_array($event, $this->events, true);
    }
}
