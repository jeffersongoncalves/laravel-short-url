<?php

namespace JeffersonGoncalves\LaravelShortUrl\Registries;

use JeffersonGoncalves\LaravelShortUrl\Data\PixelProvider;

class PixelProviderRegistry
{
    /**
     * @var array<string, PixelProvider>
     */
    protected array $providers = [];

    public function register(PixelProvider $provider): void
    {
        $this->providers[$provider->key] = $provider;
    }

    public function get(string $key): ?PixelProvider
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * @return array<string, PixelProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function registerDefaults(): void
    {
        foreach ($this->defaults() as $provider) {
            $this->register($provider);
        }
    }

    /**
     * @return array<int, PixelProvider>
     */
    protected function defaults(): array
    {
        return [
            new PixelProvider(
                'meta_pixel',
                'Meta Pixel',
                [['key' => 'pixel_id', 'label' => 'Pixel ID', 'type' => 'text']],
                <<<'JS'
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                document,'script','https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{pixel_id}');
                fbq('track', 'PageView');
                JS
            ),
            new PixelProvider(
                'google_ads',
                'Google Ads',
                [['key' => 'conversion_id', 'label' => 'Conversion ID', 'type' => 'text']],
                <<<'JS'
                var s=document.createElement('script');
                s.src='https://www.googletagmanager.com/gtag/js?id={conversion_id}';
                s.async=true; document.head.appendChild(s);
                window.dataLayer=window.dataLayer||[];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{conversion_id}');
                JS
            ),
            new PixelProvider(
                'tiktok_pixel',
                'TikTok Pixel',
                [['key' => 'pixel_id', 'label' => 'Pixel ID', 'type' => 'text']],
                <<<'JS'
                !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
                ttq.load=function(e){var s=d.createElement('script');s.async=!0;
                s.src='https://analytics.tiktok.com/i18n/pixel/events.js?sdkid='+e;
                d.getElementsByTagName('script')[0].parentNode.insertBefore(s,d.getElementsByTagName('script')[0]);};
                ttq.load('{pixel_id}'); ttq.page();}(window,document,'ttq');
                JS
            ),
            new PixelProvider(
                'google_analytics',
                'Google Analytics (GA4)',
                [['key' => 'measurement_id', 'label' => 'Measurement ID', 'type' => 'text']],
                <<<'JS'
                var s=document.createElement('script');
                s.src='https://www.googletagmanager.com/gtag/js?id={measurement_id}';
                s.async=true; document.head.appendChild(s);
                window.dataLayer=window.dataLayer||[];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{measurement_id}');
                JS
            ),
        ];
    }
}
