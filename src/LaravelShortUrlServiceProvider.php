<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\AggregateAndPruneCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\SyncCountersCommand;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\HeadersGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\IpApiGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\MaxMindGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Observers\ShortUrlObserver;
use JeffersonGoncalves\LaravelShortUrl\Policies\ShortUrlPolicy;
use JeffersonGoncalves\LaravelShortUrl\Repositories\EloquentVisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;
use JeffersonGoncalves\LaravelShortUrl\Settings\DatabaseSettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Stats\EloquentStatsAggregator;
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
                'create_short_url_visits_table',
                'create_short_url_daily_stats_table',
            ])
            ->hasTranslations()
            ->hasRoute('web')
            ->hasCommands([
                SyncCountersCommand::class,
                AggregateAndPruneCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SettingsRepository::class, DatabaseSettingsRepository::class);
        $this->app->singleton(ShortUrlManager::class);
        $this->app->singleton(CounterBuffer::class);

        $this->app->singleton(VisitRepository::class, fn () => match (config('short-url.tracking.driver', 'eloquent')) {
            default => new EloquentVisitRepository,
        });

        $this->app->bind(StatsAggregator::class, EloquentStatsAggregator::class);

        $this->app->bind(GeoIpDriver::class, fn ($app) => match (config('short-url.tracking.geoip.driver', 'headers')) {
            'ip_api' => new IpApiGeoIpDriver,
            'maxmind' => new MaxMindGeoIpDriver,
            default => new HeadersGeoIpDriver($app['request']),
        });
    }

    public function packageBooted(): void
    {
        ShortUrl::observe(ShortUrlObserver::class);

        Gate::policy(ShortUrl::class, ShortUrlPolicy::class);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            if (config('short-url.tracking.counter_buffering', false)) {
                $schedule->command(SyncCountersCommand::class)->everyMinute();
            }

            $schedule->command(AggregateAndPruneCommand::class)->dailyAt('02:00');
        });
    }
}
