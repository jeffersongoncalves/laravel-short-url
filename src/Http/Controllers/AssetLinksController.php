<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves /.well-known/assetlinks.json for Android App Links. Disabled by
 * default (short-url.deep_links.assetlinks.enabled) — package/fingerprint
 * come from config, supplied by the host app.
 */
class AssetLinksController
{
    public function __invoke(): JsonResponse
    {
        if (! config('short-url.deep_links.assetlinks.enabled', false)) {
            throw new NotFoundHttpException;
        }

        $apps = (array) config('short-url.deep_links.assetlinks.apps', []);

        $entries = array_map(fn (array $app) => [
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => $app['package'] ?? '',
                'sha256_cert_fingerprints' => $app['fingerprints'] ?? [],
            ],
        ], $apps);

        return response()->json($entries, 200, ['Content-Type' => 'application/json']);
    }
}
