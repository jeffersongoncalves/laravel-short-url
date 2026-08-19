<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\VisitFactory;

/**
 * @property int $id
 * @property int $short_url_id
 * @property int|null $tenant_id
 * @property Carbon $visited_at
 * @property string|null $ip_hash
 * @property string|null $ip_anonymized
 * @property int|null $ip_version
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $operating_system
 * @property string|null $operating_system_version
 * @property string|null $country
 * @property string|null $country_code
 * @property string|null $region
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property string|null $isp
 * @property string|null $asn
 * @property string|null $referer_url
 * @property string|null $referer_host
 * @property string|null $referer_type
 * @property string|null $browser_language
 * @property string|null $user_agent_hash
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property bool $is_bot
 * @property bool $is_vpn
 * @property bool $is_proxy
 * @property bool $is_tor
 * @property bool $is_datacenter
 * @property string|null $selected_variant
 * @property int|null $matched_rule_index
 * @property int|null $response_time_ms
 * @property Carbon|null $created_at
 */
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'ip_version' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_bot' => 'boolean',
            'is_vpn' => 'boolean',
            'is_proxy' => 'boolean',
            'is_tor' => 'boolean',
            'is_datacenter' => 'boolean',
            'matched_rule_index' => 'integer',
            'response_time_ms' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'visits';
    }

    protected static function newFactory(): VisitFactory
    {
        return VisitFactory::new();
    }

    /**
     * @return BelongsTo<ShortUrl, $this>
     */
    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class);
    }
}
