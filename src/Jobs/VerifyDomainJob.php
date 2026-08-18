<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelShortUrl\Contracts\DnsVerifier;
use JeffersonGoncalves\LaravelShortUrl\Contracts\WebhookDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use Throwable;

class VerifyDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $customDomainId) {}

    public function handle(DnsVerifier $verifier): void
    {
        try {
            $domain = CustomDomain::query()->find($this->customDomainId);

            if (! $domain) {
                return;
            }

            $result = $verifier->verify($domain->domain, $domain->verification_token);
            $maxFailures = (int) config('short-url.domains.max_verification_failures', 10);

            if ($result->verified) {
                $domain->forceFill([
                    'is_verified' => true,
                    'verified_at' => $result->checkedAt ?? now(),
                    'dns_record_type' => $result->method,
                    'failure_count' => 0,
                    'last_checked_at' => now(),
                ])->save();

                app(WebhookDispatcher::class)->dispatch('domain.verified', ['domain' => $domain->domain], null);

                return;
            }

            $failureCount = $domain->failure_count + 1;

            $domain->forceFill([
                'failure_count' => $failureCount,
                'last_checked_at' => now(),
                'disabled_at' => $failureCount >= $maxFailures ? now() : $domain->disabled_at,
            ])->save();

            app(WebhookDispatcher::class)->dispatch('domain.failed', [
                'domain' => $domain->domain,
                'error' => $result->error,
                'failure_count' => $failureCount,
            ], null);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
