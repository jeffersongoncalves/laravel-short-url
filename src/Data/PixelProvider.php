<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class PixelProvider
{
    /**
     * @param  array<int, array{key: string, label: string, type: string}>  $configFields  UI metadata for the plugin's pixel config form.
     * @param  string  $scriptTemplate  JS snippet fired on the interstitial. Placeholders like {pixel_id} are replaced from the pixel's stored config.
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $configFields,
        public string $scriptTemplate,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function render(array $config): string
    {
        $script = $this->scriptTemplate;

        foreach ($config as $field => $value) {
            $script = str_replace('{'.$field.'}', (string) $value, $script);
        }

        return $script;
    }
}
