<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\RedirectController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\UnlockController;

$router = Route::middleware(config('short-url.route.middleware', ['web']));

if ($domain = config('short-url.route.domain')) {
    $router = $router->domain($domain);
}

if ($prefix = config('short-url.route.prefix')) {
    $router = $router->prefix($prefix);
}

$router->group(function (): void {
    Route::post('/{urlKey}/unlock', UnlockController::class)->name('short-url.unlock');

    if (config('short-url.route.fallback', false)) {
        Route::fallback(RedirectController::class)->name('short-url.redirect');
    } else {
        Route::get('/{urlKey}', RedirectController::class)->name('short-url.redirect');
    }
});
