<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Jobs\VerifyDomainJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('lists domains', function () {
    CustomDomain::factory()->count(2)->create();

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/domains')
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});

it('creates a domain and queues verification', function () {
    Bus::fake();

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/domains', ['domain' => 'links.example.com'])
        ->assertCreated();

    expect(CustomDomain::query()->where('domain', 'links.example.com')->exists())->toBeTrue();
    Bus::assertDispatched(VerifyDomainJob::class);
});

it('rejects a duplicate domain', function () {
    CustomDomain::factory()->create(['domain' => 'links.example.com']);

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/domains', ['domain' => 'links.example.com'])
        ->assertStatus(422);
});
