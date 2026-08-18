<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use JeffersonGoncalves\LaravelShortUrl\Analytics\Ga4AnalyticsDriver;
use JeffersonGoncalves\LaravelShortUrl\Analytics\PlausibleAnalyticsDriver;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\AggregateAndPruneCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\CheckSafeBrowsingCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\DetectAnomaliesCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\PruneWebhookDeliveriesCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\SendScheduledReportsCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\SyncCountersCommand;
use JeffersonGoncalves\LaravelShortUrl\Console\Commands\VerifyDomainsCommand;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Contracts\DnsVerifier;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\QrCodeBuilder;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Contracts\TargetingResolver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VpnDetectionDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\WebhookDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Conversions\MetaCapiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Conversions\NullConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Dns\NativeDnsVerifier;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\HeadersGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\IpApiGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\GeoIp\MaxMindGeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Observers\AuditLogObserver;
use JeffersonGoncalves\LaravelShortUrl\Observers\CustomDomainObserver;
use JeffersonGoncalves\LaravelShortUrl\Observers\ShortUrlObserver;
use JeffersonGoncalves\LaravelShortUrl\Policies\ShortUrlPolicy;
use JeffersonGoncalves\LaravelShortUrl\Qr\EndroidQrCodeBuilder;
use JeffersonGoncalves\LaravelShortUrl\Registries\AnalyticsDriverRegistry;
use JeffersonGoncalves\LaravelShortUrl\Registries\DeepLinkRegistry;
use JeffersonGoncalves\LaravelShortUrl\Registries\FilterTypeRegistry;
use JeffersonGoncalves\LaravelShortUrl\Registries\PixelProviderRegistry;
use JeffersonGoncalves\LaravelShortUrl\Repositories\EloquentVisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Security\GoogleSafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Security\IpApiVpnDetectionDriver;
use JeffersonGoncalves\LaravelShortUrl\Security\ProxyCheckVpnDetectionDriver;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;
use JeffersonGoncalves\LaravelShortUrl\Settings\DatabaseSettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Stats\EloquentStatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Targeting\RuleBasedTargetingResolver;
use JeffersonGoncalves\LaravelShortUrl\Webhooks\EloquentWebhookDispatcher;
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
                'create_short_url_custom_domains_table',
                'create_short_url_audit_logs_table',
                'create_short_url_api_keys_table',
                'create_short_url_webhooks_table',
                'create_short_url_webhook_deliveries_table',
                'create_short_url_conversions_table',
                'create_short_url_alerts_table',
                'create_short_url_pixels_table',
            ])
            ->hasTranslations()
            ->hasViews()
            ->hasRoutes(['web', 'api'])
            ->hasCommands([
                SyncCountersCommand::class,
                AggregateAndPruneCommand::class,
                VerifyDomainsCommand::class,
                CheckSafeBrowsingCommand::class,
                PruneWebhookDeliveriesCommand::class,
                DetectAnomaliesCommand::class,
                SendScheduledReportsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SettingsRepository::class, DatabaseSettingsRepository::class);
        $this->app->singleton(ShortUrlManager::class);
        $this->app->singleton(CounterBuffer::class);
        $this->app->singleton(FilterTypeRegistry::class);
        $this->app->singleton(DeepLinkRegistry::class);
        $this->app->singleton(PixelProviderRegistry::class);
        $this->app->singleton(DnsVerifier::class, NativeDnsVerifier::class);
        $this->app->bind(QrCodeBuilder::class, EndroidQrCodeBuilder::class);

        $this->app->singleton(VisitRepository::class, fn () => match (config('short-url.tracking.driver', 'eloquent')) {
            default => new EloquentVisitRepository,
        });

        $this->app->bind(StatsAggregator::class, EloquentStatsAggregator::class);
        $this->app->bind(TargetingResolver::class, RuleBasedTargetingResolver::class);

        $this->app->bind(GeoIpDriver::class, fn ($app) => match (config('short-url.tracking.geoip.driver', 'headers')) {
            'ip_api' => new IpApiGeoIpDriver,
            'maxmind' => new MaxMindGeoIpDriver,
            default => new HeadersGeoIpDriver($app['request']),
        });

        $this->app->singleton(SafeBrowsingChecker::class, GoogleSafeBrowsingChecker::class);

        $this->app->bind(VpnDetectionDriver::class, fn () => match (config('short-url.security.vpn_detection.driver', 'ip_api')) {
            'proxycheck_io' => new ProxyCheckVpnDetectionDriver,
            default => new IpApiVpnDetectionDriver,
        });

        $this->app->singleton(WebhookDispatcher::class, EloquentWebhookDispatcher::class);
        $this->app->singleton(AnalyticsDriverRegistry::class);

        $this->app->bind(ConversionApiDispatcher::class, fn () => match (config('short-url.conversions.driver', 'none')) {
            'meta' => new MetaCapiDispatcher,
            default => new NullConversionApiDispatcher,
        });
    }

    public function packageBooted(): void
    {
        ShortUrl::observe(ShortUrlObserver::class);
        ShortUrl::observe(AuditLogObserver::class);
        CustomDomain::observe(CustomDomainObserver::class);

        Gate::policy(ShortUrl::class, ShortUrlPolicy::class);

        $this->app->make(FilterTypeRegistry::class)->registerDefaults();
        $this->app->make(DeepLinkRegistry::class)->registerDefaults();
        $this->app->make(PixelProviderRegistry::class)->registerDefaults();

        $analyticsDrivers = $this->app->make(AnalyticsDriverRegistry::class);
        $analyticsDrivers->extend('ga4', fn () => new Ga4AnalyticsDriver);
        $analyticsDrivers->extend('plausible', fn () => new PlausibleAnalyticsDriver);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            if (config('short-url.tracking.counter_buffering', false)) {
                $schedule->command(SyncCountersCommand::class)->everyMinute();
            }

            $schedule->command(AggregateAndPruneCommand::class)->dailyAt('02:00');

            if (config('short-url.domains.enabled', false)) {
                $schedule->command(VerifyDomainsCommand::class)->cron('0 */6 * * *');
            }

            if (config('short-url.security.safe_browsing.enabled', false)) {
                $schedule->command(CheckSafeBrowsingCommand::class)->daily();
            }

            $schedule->command(DetectAnomaliesCommand::class)->hourly();
            $schedule->command(SendScheduledReportsCommand::class)->daily();
            $schedule->command(PruneWebhookDeliveriesCommand::class)->weekly();
        });
    }
}
