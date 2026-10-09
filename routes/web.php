<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\RedirectController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\UnlockController;

$attributes = array_filter([
    'domain' => config('short-url.route.domain'),
    'prefix' => config('short-url.route.prefix'),
]);

Route::group($attributes, function (): void {
    Route::middleware(config('short-url.route.middleware', ['web']))->group(function (): void {
        Route::post('/{urlKey}/unlock', UnlockController::class)->name('short-url.unlock');

        if (config('short-url.route.fallback', false)) {
            Route::fallback(RedirectController::class)->name('short-url.redirect');
        } else {
            Route::get('/{urlKey}', RedirectController::class)->name('short-url.redirect');
        }
    });

    if (config('short-url.route.fallback', false)) {
        // The GET-only fallback above would turn every unmatched non-GET request into a 405.
        // This one catches them first and 404s; no middleware, so CSRF/session never run (419).
        Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '{fallbackPlaceholder}', RedirectController::class)
            ->where('fallbackPlaceholder', '.*')
            ->fallback();
    }
});
