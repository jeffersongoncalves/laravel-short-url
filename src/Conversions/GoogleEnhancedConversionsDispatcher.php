<?php

namespace JeffersonGoncalves\LaravelShortUrl\Conversions;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use Throwable;

/**
 * Google Ads click-conversion upload (uploadClickConversions), used for
 * Enhanced Conversions for Leads. The host app is responsible for keeping
 * access_token fresh (OAuth refresh isn't this package's concern).
 */
class GoogleEnhancedConversionsDispatcher implements ConversionApiDispatcher
{
    public function send(Conversion $conversion): void
    {
        $customerId = config('short-url.conversions.google.customer_id');
        $developerToken = config('short-url.conversions.google.developer_token');
        $accessToken = config('short-url.conversions.google.access_token');
        $conversionActionId = config('short-url.conversions.google.conversion_action_id');

        if (! $customerId || ! $developerToken || ! $accessToken || ! $conversionActionId) {
            return;
        }

        try {
            Http::timeout(3)
                ->withToken($accessToken)
                ->withHeaders(['developer-token' => $developerToken])
                ->post("https://googleads.googleapis.com/v17/customers/{$customerId}:uploadClickConversions", [
                    'conversions' => [array_filter([
                        'conversionAction' => "customers/{$customerId}/conversionActions/{$conversionActionId}",
                        'conversionDateTime' => $conversion->occurred_at->format('Y-m-d H:i:sP'),
                        'orderId' => $conversion->external_id,
                        'conversionValue' => $conversion->value,
                        'currencyCode' => $conversion->currency,
                    ])],
                    'partialFailure' => true,
                ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
