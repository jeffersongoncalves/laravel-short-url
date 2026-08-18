<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelShortUrl\Jobs\VerifyDomainJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

it('dispatches a verify job for every non-disabled domain', function () {
    Bus::fake();

    $active = CustomDomain::factory()->create();
    CustomDomain::factory()->create(['disabled_at' => now()]);

    $this->artisan('short-url:verify-domains')->assertExitCode(0);

    Bus::assertDispatched(VerifyDomainJob::class, fn (VerifyDomainJob $job) => $job->customDomainId === $active->id);
    Bus::assertDispatchedTimes(VerifyDomainJob::class, 1);
});
