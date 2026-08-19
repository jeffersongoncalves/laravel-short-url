<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\ConversionFactory;

/**
 * @property int $id
 * @property int $short_url_id
 * @property int|null $visit_id
 * @property string $event_name
 * @property float|null $value
 * @property string|null $currency
 * @property string|null $external_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 */
class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'conversions';
    }

    protected static function newFactory(): ConversionFactory
    {
        return ConversionFactory::new();
    }
}
