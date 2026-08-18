<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl as ShortUrlModel;
use JeffersonGoncalves\LaravelShortUrl\Services\KeyGenerator;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\PlanLimits;

class ShortUrlManager
{
    public function __construct(protected KeyGenerator $keyGenerator) {}

    public function create(array $attributes): ShortUrlModel
    {
        app(PlanLimits::class)->assertCanCreateLink();

        // custom_domain_id is NOT NULL (sentinel 0 = no custom domain) —
        // coerce an explicit null the same way an omitted key already
        // resolves, so callers can pass either without hitting a NOT NULL
        // constraint violation.
        if (array_key_exists('custom_domain_id', $attributes) && $attributes['custom_domain_id'] === null) {
            $attributes['custom_domain_id'] = 0;
        }

        $attributes['url_key'] ??= $this->keyGenerator->generate($attributes['custom_domain_id'] ?? null);

        $attributes += [
            'is_enabled' => true,
            'redirect_status_code' => (int) config('short-url.redirect.default_status_code', 302),
            'forward_query_params' => true,
            'destination_type' => 'single',
        ];

        return ShortUrlModel::create($attributes);
    }

    public function destination(string $url): ShortUrlBuilder
    {
        return new ShortUrlBuilder($url);
    }

    // ponytail: $host unused until custom domains land in F3, kept for contract stability
    public function resolve(string $key, ?string $host = null): ?ShortUrlModel
    {
        return ShortUrlModel::findByKey($key);
    }
}
