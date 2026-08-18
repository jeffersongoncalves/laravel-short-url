<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\FolderFactory;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $color
 */
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'folders';
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ShortUrl, $this>
     */
    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class, 'folder_id');
    }
}
