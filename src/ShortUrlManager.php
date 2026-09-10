<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\RequiredUtmParameterMissing;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl as ShortUrlModel;
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;
use JeffersonGoncalves\LaravelShortUrl\Services\KeyGenerator;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\PlanLimits;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\TenantContext;
use Throwable;

class ShortUrlManager
{
    protected const UTM_ATTRIBUTES = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function __construct(protected KeyGenerator $keyGenerator) {}

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws RequiredUtmParameterMissing
     */
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

        $attributes = $this->applyUtmTemplate($attributes);
        $this->assertRequiredUtmParametersPresent($attributes);

        $attributes['url_key'] ??= $this->keyGenerator->generate($attributes['custom_domain_id'] ?? null);

        $attributes += [
            'is_enabled' => true,
            'redirect_status_code' => (int) config('short-url.redirect.default_status_code', 302),
            'forward_query_params' => true,
            'destination_type' => 'single',
        ];

        return ShortUrlModel::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws RequiredUtmParameterMissing
     */
    public function update(ShortUrlModel $shortUrl, array $attributes): ShortUrlModel
    {
        if (array_key_exists('custom_domain_id', $attributes) && $attributes['custom_domain_id'] === null) {
            $attributes['custom_domain_id'] = 0;
        }

        $attributes = $this->applyUtmTemplate($attributes);

        // Required UTM parameters are re-checked against the state the
        // record would end up in — existing values patched with the
        // incoming ones — so a PATCH can't null one out to bypass the rule.
        $this->assertRequiredUtmParametersPresent(array_merge($shortUrl->only(self::UTM_ATTRIBUTES), $attributes));

        $shortUrl->update($attributes);

        return $shortUrl;
    }

    public function destination(string $url): ShortUrlBuilder
    {
        return new ShortUrlBuilder($url);
    }

    public function resolve(string $key, ?string $host = null): ?ShortUrlModel
    {
        $customDomainId = $host ? CustomDomain::forHost($host)?->id : null;

        return ShortUrlModel::findByKey($key, $customDomainId);
    }

    /**
     * Batch counterpart to create()/resolve() for routing many destination
     * URLs through short links at once (e.g. rewriting every outbound link
     * in a large document) — the per-URL path pays its own cache read + DB
     * lookup on every call, which turns into a multi-minute operation on a
     * document with thousands of links.
     *
     * One batched cache read covers every URL, one whereIn() query covers
     * whatever the cache missed, and only genuinely new destinations pay
     * the real create() cost (plan-limit check, key generation, insert).
     * A failure minting one URL's key falls back to that URL unchanged
     * instead of losing the rest of the batch.
     *
     * @param  array<string>  $urls
     * @return array<string, string> destination url => full short url, or the destination url unchanged if it couldn't be shortened
     */
    public function resolveMany(array $urls): array
    {
        $urls = array_values(array_unique($urls));

        if ($urls === []) {
            return [];
        }

        $cacheEnabled = config('short-url.cache.enabled', true);
        $cached = $cacheEnabled
            ? Cache::many(array_map(static::destinationCacheKey(...), $urls))
            : [];

        $results = [];
        $pending = [];

        foreach ($urls as $url) {
            $shortUrl = $cached[static::destinationCacheKey($url)] ?? null;

            if ($shortUrl instanceof ShortUrlModel) {
                $results[$url] = $shortUrl->fullUrl();
            } else {
                $pending[] = $url;
            }
        }

        if ($pending === []) {
            return $results;
        }

        $existing = ShortUrlModel::query()
            ->whereIn('destination_url', $pending)
            ->get()
            ->keyBy('destination_url');

        $toCache = [];

        foreach ($pending as $url) {
            $shortUrl = $existing->get($url);

            if (! $shortUrl) {
                try {
                    $shortUrl = $this->create(['destination_url' => $url]);
                } catch (Throwable) {
                    $results[$url] = $url;

                    continue;
                }
            }

            $results[$url] = $shortUrl->fullUrl();
            $toCache[static::destinationCacheKey($url)] = $shortUrl;
        }

        if ($cacheEnabled && $toCache !== []) {
            Cache::putMany($toCache, (int) config('short-url.cache.ttl', 3600));
        }

        return $results;
    }

    /**
     * Tenant-scoped so two tenants sharing the same destination URL never
     * see each other's cached short url.
     */
    public static function destinationCacheKey(string $destinationUrl): string
    {
        $tenantId = app(TenantContext::class)->currentId();

        return config('short-url.cache.prefix', 'short_url').':destination:'
            .($tenantId !== null ? "{$tenantId}:" : '')
            .md5($destinationUrl);
    }

    /**
     * utm_template_id fills in whichever of the five utm_* attributes the
     * caller didn't already set explicitly — explicit attributes always win.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function applyUtmTemplate(array $attributes): array
    {
        if (! array_key_exists('utm_template_id', $attributes)) {
            return $attributes;
        }

        $templateId = $attributes['utm_template_id'];
        unset($attributes['utm_template_id']);

        $template = $templateId ? UtmTemplate::query()->find($templateId) : null;

        if (! $template) {
            return $attributes;
        }

        $defaults = array_filter($template->toUtmAttributes(), fn ($value) => $value !== null);

        return array_merge($defaults, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws RequiredUtmParameterMissing
     */
    protected function assertRequiredUtmParametersPresent(array $attributes): void
    {
        foreach (config('short-url.utm.required', []) as $key) {
            if (! in_array($key, self::UTM_ATTRIBUTES, true)) {
                continue;
            }

            if (empty($attributes[$key] ?? null)) {
                throw new RequiredUtmParameterMissing($key);
            }
        }
    }
}
