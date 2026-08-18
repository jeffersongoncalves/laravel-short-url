<?php

namespace JeffersonGoncalves\LaravelShortUrl\Conversions;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use Throwable;

/**
 * LinkedIn Conversions API. conversion_id is the urn:lla:llaPartnerConversion
 * id configured in LinkedIn Campaign Manager for this event.
 */
class LinkedInCapiDispatcher implements ConversionApiDispatcher
{
    public function send(Conversion $conversion): void
    {
        $accessToken = config('short-url.conversions.linkedin.access_token');
        $conversionId = config('short-url.conversions.linkedin.conversion_id');

        if (! $accessToken || ! $conversionId) {
            return;
        }

        try {
            Http::timeout(3)
                ->withToken($accessToken)
                ->withHeaders(['LinkedIn-Version' => '202405'])
                ->post('https://api.linkedin.com/rest/conversionEvents', array_filter([
                    'conversion' => $conversionId,
                    'conversionHappenedAt' => $conversion->occurred_at->getTimestampMs(),
                    'eventId' => $conversion->external_id,
                    'conversionValue' => $conversion->value ? array_filter([
                        'currencyCode' => $conversion->currency,
                        'amount' => (string) $conversion->value,
                    ]) : null,
                ]));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
