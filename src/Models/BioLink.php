<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bio_page_id
 * @property int|null $short_url_id
 * @property string $type
 * @property string|null $label
 * @property array<string, mixed> $content
 * @property int $position
 * @property bool $is_enabled
 * @property int $click_count
 */
class BioLink extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_enabled' => 'boolean',
            'position' => 'integer',
            'click_count' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'bio_links';
    }

    /**
     * @return BelongsTo<BioPage, $this>
     */
    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }

    /**
     * @return BelongsTo<ShortUrl, $this>
     */
    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class);
    }

    public function recordClick(): void
    {
        $this->increment('click_count');
    }
}
