<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $user_id
 * @property string $handle
 * @property string|null $title
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property string $theme
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image_path
 * @property bool $is_published
 * @property int $total_views
 */
class BioPage extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'total_views' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'bio_pages';
    }

    /**
     * @return HasMany<BioLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(BioLink::class)->orderBy('position');
    }

    /**
     * @return HasMany<BioLink, $this>
     */
    public function enabledLinks(): HasMany
    {
        return $this->links()->where('is_enabled', true);
    }
}
