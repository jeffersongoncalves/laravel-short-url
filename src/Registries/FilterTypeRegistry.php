<?php

namespace JeffersonGoncalves\LaravelShortUrl\Registries;

use JeffersonGoncalves\LaravelShortUrl\Data\FilterType;

/**
 * UI metadata for targeting-rule condition types, consumed by the plugin
 * to build rule-builder forms without duplicating knowledge of what
 * filters exist. Actual matching logic lives in Targeting\ConditionMatcher.
 */
class FilterTypeRegistry
{
    /**
     * @var array<string, FilterType>
     */
    protected array $types = [];

    public function register(FilterType $filterType): void
    {
        $this->types[$filterType->key] = $filterType;
    }

    public function get(string $key): ?FilterType
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return array<string, FilterType>
     */
    public function all(): array
    {
        return $this->types;
    }

    public function registerDefaults(): void
    {
        foreach ($this->defaults() as $filterType) {
            $this->register($filterType);
        }
    }

    /**
     * @return array<int, FilterType>
     */
    protected function defaults(): array
    {
        return [
            new FilterType('device', trans('short-url::filters.device'), 'select', 'device-mobile', [
                ['value' => 'desktop', 'label' => trans('short-url::filters.desktop')],
                ['value' => 'mobile', 'label' => trans('short-url::filters.mobile')],
                ['value' => 'tablet', 'label' => trans('short-url::filters.tablet')],
            ]),
            new FilterType('platform', trans('short-url::filters.platform'), 'select', 'device-desktop', [
                ['value' => 'Windows', 'label' => 'Windows'],
                ['value' => 'macOS', 'label' => 'macOS'],
                ['value' => 'Linux', 'label' => 'Linux'],
                ['value' => 'Android', 'label' => 'Android'],
                ['value' => 'iOS', 'label' => 'iOS'],
            ]),
            new FilterType('browser', trans('short-url::filters.browser'), 'select', 'browser', [
                ['value' => 'Chrome', 'label' => 'Chrome'],
                ['value' => 'Firefox', 'label' => 'Firefox'],
                ['value' => 'Safari', 'label' => 'Safari'],
                ['value' => 'Edge', 'label' => 'Edge'],
                ['value' => 'Opera', 'label' => 'Opera'],
            ]),
            new FilterType('country', trans('short-url::filters.country'), 'text', 'globe'),
            new FilterType('region', trans('short-url::filters.region'), 'text', 'map'),
            new FilterType('city', trans('short-url::filters.city'), 'text', 'map-pin'),
            new FilterType('language', trans('short-url::filters.language'), 'text', 'language'),
            new FilterType('referer', trans('short-url::filters.referer'), 'text', 'link'),
            new FilterType('utm', trans('short-url::filters.utm'), 'key_value', 'tag'),
            new FilterType('datetime', trans('short-url::filters.datetime'), 'schedule', 'clock'),
            new FilterType('visit_count', trans('short-url::filters.visit_count'), 'number', 'chart-bar'),
            new FilterType('is_vpn', trans('short-url::filters.is_vpn'), 'boolean', 'shield'),
            new FilterType('is_bot', trans('short-url::filters.is_bot'), 'boolean', 'robot'),
            new FilterType('query_param', trans('short-url::filters.query_param'), 'key_value', 'question-mark'),
        ];
    }
}
