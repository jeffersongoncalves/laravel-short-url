<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves /.well-known/apple-app-site-association for iOS Universal Links.
 * Disabled by default (short-url.deep_links.aasa.enabled) — the operator
 * supplies their own app ID(s) via config, since the package has no way
 * to know the host app's Apple Team ID / bundle identifier.
 */
class AppleAppSiteAssociationController
{
    public function __invoke(): JsonResponse
    {
        if (! config('short-url.deep_links.aasa.enabled', false)) {
            throw new NotFoundHttpException;
        }

        $appIds = (array) config('short-url.deep_links.aasa.app_ids', []);
        $paths = (array) config('short-url.deep_links.aasa.paths', ['*']);

        return response()->json([
            'applinks' => [
                'apps' => [],
                'details' => array_map(fn (string $appId) => [
                    'appID' => $appId,
                    'paths' => $paths,
                ], $appIds),
            ],
        ], 200, ['Content-Type' => 'application/json']);
    }
}
