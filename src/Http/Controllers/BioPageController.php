<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use JeffersonGoncalves\LaravelShortUrl\Models\BioLink;
use JeffersonGoncalves\LaravelShortUrl\Models\BioPage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BioPageController
{
    public function show(string $handle): Response
    {
        if (! config('short-url.bio.enabled', false)) {
            throw new NotFoundHttpException;
        }

        $page = BioPage::query()->where('handle', $handle)->where('is_published', true)->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        $page->increment('total_views');

        return response()->view('short-url::bio-page', [
            'page' => $page,
            'links' => $page->enabledLinks()->get(),
        ]);
    }

    public function click(string $handle, BioLink $bioLink): RedirectResponse
    {
        if (! config('short-url.bio.enabled', false)) {
            throw new NotFoundHttpException;
        }

        if ($bioLink->bioPage->handle !== $handle || ! $bioLink->is_enabled) {
            throw new NotFoundHttpException;
        }

        $bioLink->recordClick();

        if ($bioLink->short_url_id && $bioLink->shortUrl) {
            // Deliberately not route('short-url.redirect', ...): when
            // short-url.route.fallback is on, that route is a catch-all
            // Route::fallback() and can't be reverse-generated with a
            // urlKey parameter.
            return redirect()->away(url($bioLink->shortUrl->url_key));
        }

        $url = $bioLink->content['url'] ?? null;

        if (! $url) {
            throw new NotFoundHttpException;
        }

        return redirect()->away($url);
    }
}
