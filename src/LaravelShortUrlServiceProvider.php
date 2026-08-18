<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use Illuminate\Support\Facades\Gate;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Observers\ShortUrlObserver;
use JeffersonGoncalves\LaravelShortUrl\Policies\ShortUrlPolicy;
use JeffersonGoncalves\LaravelShortUrl\Settings\DatabaseSettingsRepository;
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
            ->hasTranslations()
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SettingsRepository::class, DatabaseSettingsRepository::class);
        $this->app->singleton(ShortUrlManager::class);
    }

    public function packageBooted(): void
    {
        ShortUrl::observe(ShortUrlObserver::class);

        Gate::policy(ShortUrl::class, ShortUrlPolicy::class);
    }
}
