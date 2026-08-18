<?php

namespace JeffersonGoncalves\LaravelShortUrl\Security;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Data\SafetyResult;
use Throwable;

/**
 * Google Safe Browsing v4 threatMatches.find. A missing API key or any
 * request failure yields "unknown" rather than throwing — safety scanning
 * must never be the reason a link can't be saved.
 */
class GoogleSafeBrowsingChecker implements SafeBrowsingChecker
{
    protected const ENDPOINT = 'https://safebrowsing.googleapis.com/v4/threatMatches:find';

    public function check(string $url): SafetyResult
    {
        $apiKey = config('short-url.security.safe_browsing.api_key');

        if (! $apiKey) {
            return new SafetyResult('unknown', now());
        }

        try {
            $response = Http::timeout(3)->post(self::ENDPOINT.'?key='.$apiKey, [
                'client' => [
                    'clientId' => 'laravel-short-url',
                    'clientVersion' => '1.0.0',
                ],
                'threatInfo' => [
                    'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                    'platformTypes' => ['ANY_PLATFORM'],
                    'threatEntryTypes' => ['URL'],
                    'threatEntries' => [['url' => $url]],
                ],
            ]);

            if (! $response->successful()) {
                return new SafetyResult('unknown', now());
            }

            $matches = $response->json('matches', []);

            if ($matches === [] || $matches === null) {
                return new SafetyResult('safe', now());
            }

            $threats = array_values(array_unique(array_map(
                fn (array $match) => (string) ($match['threatType'] ?? 'UNKNOWN'),
                $matches
            )));

            return new SafetyResult('unsafe', now(), $threats);
        } catch (Throwable $e) {
            report($e);

            return new SafetyResult('unknown', now());
        }
    }
}
