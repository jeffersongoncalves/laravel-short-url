<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Support\PasswordUnlock;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnlockController
{
    public function __invoke(Request $request, string $urlKey): RedirectResponse
    {
        $shortUrl = ShortUrl::findByKey($urlKey);

        if (! $shortUrl || ! $shortUrl->password_hash) {
            throw new NotFoundHttpException;
        }

        $request->validate(['password' => 'required|string']);

        if (! Hash::check((string) $request->input('password'), $shortUrl->password_hash)) {
            return back()->withErrors(['password' => trans('short-url::interstitials.invalid_password')])->withInput();
        }

        PasswordUnlock::unlock($shortUrl->id);

        $prefix = trim((string) config('short-url.route.prefix'), '/');
        $path = $prefix !== '' ? "{$prefix}/{$urlKey}" : $urlKey;

        return redirect()->to('/'.$path);
    }
}
