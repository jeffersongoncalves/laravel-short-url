<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

class VisitController
{
    public function index(Request $request, ShortUrl $shortUrl, VisitRepository $visits): JsonResponse
    {
        $filters = array_filter($request->only(['country_code', 'device_type', 'is_bot', 'is_qr_scan']), fn ($v) => $v !== null);

        return response()->json(['data' => $visits->query($shortUrl->id, $filters)]);
    }
}
