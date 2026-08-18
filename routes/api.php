<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\ConversionController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\DomainController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\LinkController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\StatsController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\VisitController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api\WebhookController;
use JeffersonGoncalves\LaravelShortUrl\Http\Middleware\ApiKeyAuth;

// Registered unconditionally — short-url.api.enabled is checked at request
// time by ApiKeyAuth instead of at boot, so it works with route caching and
// with config changed after the app has booted (e.g. in tests).
Route::prefix(config('short-url.api.prefix', 'api/short-url/v1'))
    ->middleware(['api'])
    ->name('short-url.api.')
    ->group(function (): void {
        Route::middleware(ApiKeyAuth::class.':links:read')->group(function (): void {
            Route::get('links', [LinkController::class, 'index'])->name('links.index');
            Route::get('links/{shortUrl:uuid}', [LinkController::class, 'show'])->name('links.show');
            Route::get('links/{shortUrl:uuid}/stats', [StatsController::class, 'show'])->name('links.stats');
            Route::get('links/{shortUrl:uuid}/visits', [VisitController::class, 'index'])->name('links.visits');
            Route::get('domains', [DomainController::class, 'index'])->name('domains.index');
            Route::get('webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        });

        Route::middleware(ApiKeyAuth::class.':links:write')->group(function (): void {
            Route::post('links', [LinkController::class, 'store'])->name('links.store');
            Route::post('links/bulk', [LinkController::class, 'bulkStore'])->name('links.bulk');
            Route::patch('links/{shortUrl:uuid}', [LinkController::class, 'update'])->name('links.update');
            Route::delete('links/{shortUrl:uuid}', [LinkController::class, 'destroy'])->name('links.destroy');
            Route::post('links/{shortUrl:uuid}/restore', [LinkController::class, 'restore'])
                ->name('links.restore')
                ->withTrashed();
            Route::post('domains', [DomainController::class, 'store'])->name('domains.store');
            Route::post('webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
            Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
            Route::post('webhook-deliveries/{delivery}/replay', [WebhookController::class, 'replay'])->name('webhooks.replay');
        });

        Route::middleware(ApiKeyAuth::class.':conversions:write')
            ->post('conversions', [ConversionController::class, 'store'])
            ->name('conversions.store');
    });
