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
        [$from, $to] = $this->range($request);

        $payload = $aggregator->for($shortUrl)->between($from, $to)->get();

        return response()->json(['data' => (array) $payload]);
    }

    /**
     * Breakdown across every link the caller can see — optionally narrowed
     * to one folder or tag — instead of a single link. Link selection
     * happens here via ShortUrl's own tenant-scoped query; StatsAggregator
     * only does the aggregation math (see forShortUrls()).
     */
    public function global(Request $request, StatsAggregator $aggregator): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $query = ShortUrl::query();

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->integer('folder_id'));
        }

        if ($request->filled('tag_id')) {
            $tagsTable = config('short-url.table_prefix', 'short_url_').'tags';
            $query->whereHas('tags', fn ($tags) => $tags->where($tagsTable.'.id', $request->integer('tag_id')));
        }

        $shortUrlIds = $query->pluck('id')->all();
        $payload = $aggregator->forShortUrls($shortUrlIds)->between($from, $to)->get();

        return response()->json(['data' => (array) $payload]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function range(Request $request): array
    {
        return [
            $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : now()->subDays(30),
            $request->filled('to') ? Carbon::parse($request->string('to')->toString()) : now(),
        ];
    }
}
