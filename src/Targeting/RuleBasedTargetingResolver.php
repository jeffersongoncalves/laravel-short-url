<?php

namespace JeffersonGoncalves\LaravelShortUrl\Targeting;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\TargetingResolver;
use JeffersonGoncalves\LaravelShortUrl\Data\Destination;
use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Support\AcceptLanguage;
use JeffersonGoncalves\LaravelShortUrl\Support\BotDetector;
use JeffersonGoncalves\LaravelShortUrl\Support\RefererClassifier;
use JeffersonGoncalves\LaravelShortUrl\Support\UserAgentParser;
use Throwable;

/**
 * Resolves destination_type "rules" (top-down conditional rules, first
 * match wins, legacy flat-list format auto-adapted) and "split" (weighted
 * A/B rotation, optionally sticky per visitor). "single" and anything
 * unrecognized fall back to the short url's base destination_url.
 */
class RuleBasedTargetingResolver implements TargetingResolver
{
    public function __construct(protected GeoIpDriver $geoIp) {}

    public function resolve(ShortUrl $shortUrl, Request $request): Destination
    {
        return match ($shortUrl->destination_type) {
            'rules' => $this->resolveRules($shortUrl, $request),
            'split' => $this->resolveSplit($shortUrl, $request, $shortUrl->rotation_variants ?? []),
            default => new Destination($shortUrl->destination_url),
        };
    }

    protected function resolveRules(ShortUrl $shortUrl, Request $request): Destination
    {
        $rules = $this->normalizeRules($shortUrl->targeting_rules ?? []);

        if ($rules === []) {
            return new Destination($shortUrl->destination_url);
        }

        $context = $this->buildContext($shortUrl, $request);

        foreach ($rules as $index => $rule) {
            if ($this->ruleMatches($rule, $context)) {
                if (! empty($rule['split']) && is_array($rule['split'])) {
                    $destination = $this->resolveSplit($shortUrl, $request, $rule['split']);

                    return new Destination($destination->url, $destination->variant, $index);
                }

                if (! empty($rule['destination'])) {
                    return new Destination((string) $rule['destination'], null, $index);
                }
            }
        }

        return new Destination($shortUrl->destination_url);
    }

    /**
     * Accepts either a plain variant list `[{url,weight}, ...]` or an
     * object shape `{sticky: bool, variants: [...]}`.
     *
     * @param  array<array-key, mixed>  $variants
     */
    protected function resolveSplit(ShortUrl $shortUrl, Request $request, array $variants): Destination
    {
        $sticky = (bool) ($variants['sticky'] ?? false);
        $list = is_array($variants['variants'] ?? null) ? $variants['variants'] : $variants;
        $list = array_values(array_filter($list, 'is_array'));

        if ($list === []) {
            return new Destination($shortUrl->destination_url);
        }

        $stickyKey = $sticky ? "{$shortUrl->id}:{$request->ip()}" : null;
        $variant = WeightedRotator::pick($list, $stickyKey);

        if (! $variant) {
            return new Destination($shortUrl->destination_url);
        }

        return new Destination($variant['url'], $variant['label'] ?? $variant['url']);
    }

    /**
     * Adapts a legacy flat-list rule format (no explicit "match" key,
     * implicit AND) into the current shape.
     *
     * @param  array<int|string, mixed>  $rules
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRules(array $rules): array
    {
        if (isset($rules['conditions']) || isset($rules['rules'])) {
            $rules = $rules['rules'] ?? [$rules];
        }

        return array_values(array_map(function ($rule) {
            if (! is_array($rule)) {
                return ['conditions' => [], 'match' => 'and'];
            }

            $rule['match'] ??= 'and';
            $rule['conditions'] ??= [];

            return $rule;
        }, $rules));
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    protected function ruleMatches(array $rule, array $context): bool
    {
        $conditions = $rule['conditions'] ?? [];

        if ($conditions === []) {
            return true;
        }

        $results = array_map(fn (array $condition) => ConditionMatcher::matches($condition, $context), $conditions);

        return ($rule['match'] ?? 'and') === 'or'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildContext(ShortUrl $shortUrl, Request $request): array
    {
        $userAgent = (string) $request->userAgent();
        $parsed = UserAgentParser::parse($userAgent);
        $ip = (string) $request->ip();
        $referer = $request->headers->get('referer');

        return [
            'device_type' => UserAgentParser::fastDeviceType($userAgent),
            'operating_system' => $parsed['operating_system'],
            'browser' => $parsed['browser'],
            'browser_language' => AcceptLanguage::preferred($request->headers->get('accept-language')),
            'referer_host' => $referer ? parse_url($referer, PHP_URL_HOST) : null,
            'referer_type' => RefererClassifier::classify($referer, (string) $request->getHost()),
            'utm' => [
                'source' => $request->query('utm_source'),
                'medium' => $request->query('utm_medium'),
                'campaign' => $request->query('utm_campaign'),
                'term' => $request->query('utm_term'),
                'content' => $request->query('utm_content'),
            ],
            'query' => $request->query(),
            'visit_count' => $shortUrl->total_visits,
            'is_bot' => BotDetector::isBot($userAgent),
            'is_vpn' => false,
            ...$this->resolveGeo($ip),
        ];
    }

    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    protected function resolveGeo(string $ip): array
    {
        try {
            $location = $ip !== '' ? $this->geoIp->resolve($ip) : new GeoLocation;
        } catch (Throwable) {
            $location = new GeoLocation;
        }

        return [
            'country_code' => $location->countryCode,
            'region' => $location->region,
            'city' => $location->city,
        ];
    }
}
