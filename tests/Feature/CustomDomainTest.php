<?php

use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

it('resolves an exact verified domain match', function () {
    CustomDomain::factory()->verified()->create(['domain' => 'links.test']);

    expect(CustomDomain::forHost('links.test'))->not->toBeNull()
        ->and(CustomDomain::forHost('other.test'))->toBeNull();
});

it('does not resolve an unverified domain', function () {
    CustomDomain::factory()->create(['domain' => 'links.test', 'is_verified' => false]);

    expect(CustomDomain::forHost('links.test'))->toBeNull();
});

it('does not resolve a disabled domain', function () {
    CustomDomain::factory()->verified()->create(['domain' => 'links.test', 'disabled_at' => now()]);

    expect(CustomDomain::forHost('links.test'))->toBeNull();
});

it('matches a subdomain against a wildcard domain', function () {
    CustomDomain::factory()->verified()->create(['domain' => 'acme.test', 'is_wildcard' => true]);

    expect(CustomDomain::forHost('go.acme.test'))->not->toBeNull()
        ->and(CustomDomain::forHost('acme.test'))->not->toBeNull()
        ->and(CustomDomain::forHost('acme.test.evil.com'))->toBeNull();
});

it('generates a verification token automatically', function () {
    $domain = CustomDomain::factory()->create(['verification_token' => null]);

    expect($domain->verification_token)->not->toBeNull();
});
