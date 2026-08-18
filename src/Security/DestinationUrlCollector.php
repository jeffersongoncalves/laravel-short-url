<?php

namespace JeffersonGoncalves\LaravelShortUrl\Security;

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * Every URL a visitor could actually be sent to for a given short url:
 * the base destination plus every split/rotation variant and every rule's
 * destination (including nested rule splits).
 */
class DestinationUrlCollector
{
    /**
     * @return array<int, string>
     */
    public static function collect(ShortUrl $shortUrl): array
    {
        $urls = [$shortUrl->destination_url];

        foreach (self::variantUrls($shortUrl->rotation_variants ?? []) as $url) {
            $urls[] = $url;
        }

        foreach (self::ruleUrls($shortUrl->targeting_rules ?? []) as $url) {
            $urls[] = $url;
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * @param  array<array-key, mixed>  $variants
     * @return array<int, string>
     */
    protected static function variantUrls(array $variants): array
    {
        $list = is_array($variants['variants'] ?? null) ? $variants['variants'] : $variants;

        return array_values(array_filter(array_map(
            fn (array $variant) => is_string($variant['url'] ?? null) ? $variant['url'] : null,
            array_filter($list, 'is_array')
        )));
    }

    /**
     * @param  array<int|string, mixed>  $rules
     * @return array<int, string>
     */
    protected static function ruleUrls(array $rules): array
    {
        if (isset($rules['conditions']) || isset($rules['rules'])) {
            $rules = $rules['rules'] ?? [$rules];
        }

        $urls = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            if (is_string($rule['destination'] ?? null)) {
                $urls[] = $rule['destination'];
            }

            if (is_array($rule['split'] ?? null)) {
                array_push($urls, ...self::variantUrls($rule['split']));
            }
        }

        return $urls;
    }
}
