<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ApiKey;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

/**
 * @param  array<int, string>  $abilities
 */
function apiToken(array $abilities = ['*']): string
{
    return ApiKey::generate('test-key', $abilities)['token'];
}

/**
 * @param  array<int, string>  $abilities
 * @return array<string, string>
 */
function apiHeaders(array $abilities = ['*']): array
{
    return ['Authorization' => 'Bearer '.apiToken($abilities)];
}

it('returns 404 when the api is disabled', function () {
    config(['short-url.api.enabled' => false]);

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/links')
        ->assertStatus(404);
});

it('rejects a request with no bearer token', function () {
    $this->getJson('/api/short-url/v1/links')->assertStatus(401);
});

it('rejects an invalid token', function () {
    $this->withHeaders(['Authorization' => 'Bearer not-a-real-token'])
        ->getJson('/api/short-url/v1/links')
        ->assertStatus(401);
});

it('rejects a revoked token', function () {
    ['token' => $token, 'key' => $key] = ApiKey::generate('revoked', ['*']);
    $key->update(['revoked_at' => now()]);

    $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson('/api/short-url/v1/links')
        ->assertStatus(401);
});

it('rejects an expired token', function () {
    ['token' => $token] = ApiKey::generate('expired', ['*'], now()->subDay());

    $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson('/api/short-url/v1/links')
        ->assertStatus(401);
});

it('rejects a token missing the required ability', function () {
    $this->withHeaders(apiHeaders(['stats:read']))
        ->getJson('/api/short-url/v1/links')
        ->assertStatus(403);
});

it('allows a request with a valid token and the right ability', function () {
    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/links')
        ->assertOk();
});

it('sets rate limit headers on a successful response', function () {
    $response = $this->withHeaders(apiHeaders(['links:read']))->getJson('/api/short-url/v1/links');

    $response->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining');
});

it('returns 429 once the per-key rate limit is exceeded', function () {
    config(['short-url.api.rate_limit.max_attempts' => 1]);
    $headers = apiHeaders(['links:read']);

    $this->withHeaders($headers)->getJson('/api/short-url/v1/links')->assertOk();
    $this->withHeaders($headers)->getJson('/api/short-url/v1/links')->assertStatus(429);
});
