<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\AppleAppSiteAssociationController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\AssetLinksController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\QrCodeController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\RedirectController;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\UnlockController;

// Always at the domain root regardless of short-url.route.prefix — the
// well-known URIs are a fixed OS-level convention, not part of this
// package's own URL namespace. Registered unconditionally; each controller
// checks its own enabled flag at request time (see ApiKeyAuth for why —
// route registration happens once at boot, before config can be toggled).
Route::get('/.well-known/apple-app-site-association', AppleAppSiteAssociationController::class)
    ->name('short-url.aasa');

Route::get('/.well-known/assetlinks.json', AssetLinksController::class)
    ->name('short-url.assetlinks');

$router = Route::middleware(config('short-url.route.middleware', ['web']));

if ($domain = config('short-url.route.domain')) {
    $router = $router->domain($domain);
}

if ($prefix = config('short-url.route.prefix')) {
    $router = $router->prefix($prefix);
}

$router->group(function (): void {
    Route::post('/{urlKey}/unlock', UnlockController::class)->name('short-url.unlock');
    Route::get('/{urlKey}/qr', QrCodeController::class)->name('short-url.qr');

    if (config('short-url.route.fallback', false)) {
        Route::fallback(RedirectController::class)->name('short-url.redirect');
    } else {
        Route::get('/{urlKey}', RedirectController::class)->name('short-url.redirect');
    }
});
