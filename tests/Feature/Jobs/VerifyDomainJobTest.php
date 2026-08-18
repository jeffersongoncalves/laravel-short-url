<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\DnsVerifier;
use JeffersonGoncalves\LaravelShortUrl\Data\VerificationResult;
use JeffersonGoncalves\LaravelShortUrl\Jobs\VerifyDomainJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

it('marks a domain verified and resets the failure count on success', function () {
    app()->bind(DnsVerifier::class, fn () => new class implements DnsVerifier
    {
        public function verify(string $domain, string $expectedToken): VerificationResult
        {
            return new VerificationResult(true, 'txt', now());
        }
    });

    $domain = CustomDomain::factory()->create(['is_verified' => false, 'failure_count' => 3]);

    (new VerifyDomainJob($domain->id))->handle(app(DnsVerifier::class));

    $domain->refresh();

    expect($domain->is_verified)->toBeTrue()
        ->and($domain->failure_count)->toBe(0)
        ->and($domain->dns_record_type)->toBe('txt');
});

it('increments the failure count and disables after the configured threshold', function () {
    config(['short-url.domains.max_verification_failures' => 2]);

    app()->bind(DnsVerifier::class, fn () => new class implements DnsVerifier
    {
        public function verify(string $domain, string $expectedToken): VerificationResult
        {
            return new VerificationResult(false, null, now(), 'no record');
        }
    });

    $domain = CustomDomain::factory()->create(['failure_count' => 1]);

    (new VerifyDomainJob($domain->id))->handle(app(DnsVerifier::class));

    $domain->refresh();

    expect($domain->failure_count)->toBe(2)
        ->and($domain->disabled_at)->not->toBeNull();
});
