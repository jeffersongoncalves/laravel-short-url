<?php

namespace JeffersonGoncalves\LaravelShortUrl\Registries;

use JeffersonGoncalves\LaravelShortUrl\Data\AppDefinition;

class DeepLinkRegistry
{
    /**
     * @var array<string, AppDefinition>
     */
    protected array $apps = [];

    public function register(AppDefinition $app): void
    {
        $this->apps[$app->key] = $app;
    }

    /**
     * @return array<string, AppDefinition>
     */
    public function all(): array
    {
        return $this->apps;
    }

    /**
     * Finds the first registered app whose host list matches the given
     * destination URL's host.
     */
    public function forUrl(string $url): ?AppDefinition
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        foreach ($this->apps as $app) {
            if ($app->matches($host)) {
                return $app;
            }
        }

        return null;
    }

    public function registerDefaults(): void
    {
        foreach ($this->defaults() as $app) {
            $this->register($app);
        }
    }

    /**
     * @return array<int, AppDefinition>
     */
    protected function defaults(): array
    {
        return [
            new AppDefinition('instagram', 'Instagram', ['instagram.com'], 'instagram://web?url={url}', 389801252, 'com.instagram.android'),
            new AppDefinition('twitter', 'X (Twitter)', ['twitter.com', 'x.com'], 'twitter://open_url?url={url}', 333903271, 'com.twitter.android'),
            new AppDefinition('facebook', 'Facebook', ['facebook.com', 'fb.com'], 'fb://facewebmodal/f?href={url}', 284882215, 'com.facebook.katana'),
            new AppDefinition('youtube', 'YouTube', ['youtube.com', 'youtu.be'], 'youtube://{url}', 544007664, 'com.google.android.youtube'),
            new AppDefinition('spotify', 'Spotify', ['open.spotify.com'], 'spotify://{url}', 324684580, 'com.spotify.music'),
            new AppDefinition('whatsapp', 'WhatsApp', ['wa.me', 'whatsapp.com'], 'whatsapp://send?text={url}', 310633997, 'com.whatsapp'),
            new AppDefinition('tiktok', 'TikTok', ['tiktok.com'], 'tiktok://{url}', 835599320, 'com.zhiliaoapp.musically'),
            new AppDefinition('linkedin', 'LinkedIn', ['linkedin.com'], 'linkedin://{url}', 288429040, 'com.linkedin.android'),
            new AppDefinition('pinterest', 'Pinterest', ['pinterest.com'], 'pinterest://{url}', 429047995, 'com.pinterest'),
            new AppDefinition('telegram', 'Telegram', ['t.me', 'telegram.me'], 'tg://resolve?domain={url}', 686449807, 'org.telegram.messenger'),
        ];
    }
}
