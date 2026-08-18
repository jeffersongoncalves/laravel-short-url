<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string|null $color
 */
class Tag extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'tags';
    }

    /**
     * @return BelongsToMany<ShortUrl, $this>
     */
    public function shortUrls(): BelongsToMany
    {
        return $this->belongsToMany(
            ShortUrl::class,
            config('short-url.table_prefix', 'short_url_').'tag_short_url'
        );
    }
}
