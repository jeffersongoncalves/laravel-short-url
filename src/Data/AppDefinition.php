<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class AppDefinition
{
    /**
     * @param  array<int, string>  $hosts  Destination hosts this app matches (e.g. "instagram.com", "open.spotify.com").
     * @param  string  $scheme  Custom URL scheme template; "{url}" is replaced with the urlencoded destination.
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $hosts,
        public string $scheme,
        public int|string|null $iosAppStoreId = null,
        public ?string $androidPackage = null,
    ) {}

    public function matches(string $host): bool
    {
        $host = strtolower($host);

        foreach ($this->hosts as $candidate) {
            if ($host === $candidate || str_ends_with($host, '.'.$candidate)) {
                return true;
            }
        }

        return false;
    }

    public function buildSchemeUrl(string $destinationUrl): string
    {
        return str_replace('{url}', urlencode($destinationUrl), $this->scheme);
    }
}
