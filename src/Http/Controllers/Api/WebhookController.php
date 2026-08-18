<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Jobs\SendWebhookJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

class WebhookController
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Webhook::query()->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'short_url_id' => ['nullable', 'integer'],
            'url' => ['required', 'url'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
        ]);

        $webhook = Webhook::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $webhook->fresh()], 201);
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();

        return response()->json(null, 204);
    }

    public function replay(WebhookDelivery $delivery): JsonResponse
    {
        SendWebhookJob::dispatch($delivery->webhook_id, $delivery->event, $delivery->payload);

        return response()->json(['data' => ['queued' => true]], 202);
    }
}
