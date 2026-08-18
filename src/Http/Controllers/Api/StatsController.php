<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

class StatsController
{
    public function show(Request $request, ShortUrl $shortUrl, StatsAggregator $aggregator): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : now()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString()) : now();

        $payload = $aggregator->for($shortUrl)->between($from, $to)->get();

        return response()->json(['data' => (array) $payload]);
    }
}
