<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Observers\ShortUrlObserver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelShortUrlServiceProvider extends PackageServiceProvider
{
    public static string $name = 'laravel-short-url';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('short-url')
            ->hasMigrations([
                'create_short_urls_table',
                'create_short_url_settings_table',
            ])
            ->hasRoute('web');
    }

    public function packageBooted(): void
    {
        ShortUrl::observe(ShortUrlObserver::class);
    }
}
