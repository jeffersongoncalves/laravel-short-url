<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelShortUrl\Http\Controllers\RedirectController;

$router = Route::middleware(config('short-url.route.middleware', ['web']));

if ($domain = config('short-url.route.domain')) {
    $router = $router->domain($domain);
}

if ($prefix = config('short-url.route.prefix')) {
    $router = $router->prefix($prefix);
}

$router->group(function (): void {
    Route::get('/{urlKey}', RedirectController::class)->name('short-url.redirect');
});
