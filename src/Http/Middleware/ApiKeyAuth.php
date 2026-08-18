<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use JeffersonGoncalves\LaravelShortUrl\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        if (! config('short-url.api.enabled', false)) {
            return $this->error(404, 'not_found', 'The API is not enabled on this installation.');
        }

        $token = $request->bearerToken();

        if (! $token) {
            return $this->error(401, 'missing_token', 'An Authorization: Bearer token is required.');
        }

        $apiKey = ApiKey::query()->where('key_hash', hash('sha256', $token))->first();

        if (! $apiKey || ! $apiKey->isValid()) {
            return $this->error(401, 'invalid_token', 'Invalid, revoked or expired API key.');
        }

        foreach ($abilities as $ability) {
            if (! $apiKey->can($ability)) {
                return $this->error(403, 'forbidden', "This key is missing the '{$ability}' ability.");
            }
        }

        $limiterKey = 'short-url-api:'.$apiKey->id;
        $maxAttempts = (int) config('short-url.api.rate_limit.max_attempts', 300);

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return $this->error(429, 'too_many_requests', 'API rate limit exceeded.', [
                'Retry-After' => (string) RateLimiter::availableIn($limiterKey),
            ]);
        }

        RateLimiter::hit($limiterKey, (int) config('short-url.api.rate_limit.decay_seconds', 60));
        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('short_url_api_key', $apiKey);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($limiterKey, $maxAttempts));

        return $response;
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function error(int $status, string $code, string $message, array $headers = []): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status, $headers);
    }
}
