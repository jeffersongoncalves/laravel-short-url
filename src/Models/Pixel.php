<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\PixelFactory;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $provider_key
 * @property array<string, mixed> $config
 */
class Pixel extends Model
{
    /** @use HasFactory<PixelFactory> */
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'pixels';
    }

    protected static function newFactory(): PixelFactory
    {
        return PixelFactory::new();
    }

    /**
     * @return BelongsToMany<ShortUrl, $this>
     */
    public function shortUrls(): BelongsToMany
    {
        return $this->belongsToMany(
            ShortUrl::class,
            config('short-url.table_prefix', 'short_url_').'pixel_short_url'
        );
    }
}
