<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * @mixin ShortUrl
 */
class ShortUrlResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'url_key' => $this->url_key,
            'short_url' => $this->fullUrl(),
            'destination_url' => $this->destination_url,
            'destination_type' => $this->destination_type,
            'title' => $this->title,
            'notes' => $this->notes,
            'custom_domain_id' => $this->custom_domain_id ?: null,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
            'is_enabled' => $this->is_enabled,
            'redirect_status_code' => $this->redirect_status_code,
            'single_use' => $this->single_use,
            'max_visits' => $this->max_visits,
            'total_visits' => $this->total_visits,
            'unique_visits' => $this->unique_visits,
            'qr_scans' => $this->qr_scans,
            'bot_visits' => $this->bot_visits,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'has_password' => (bool) $this->password_hash,
            'safe_browsing_status' => $this->safe_browsing_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
