<?php

namespace JeffersonGoncalves\LaravelShortUrl\Console\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelShortUrl\Jobs\VerifyDomainJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

class VerifyDomainsCommand extends Command
{
    protected $signature = 'short-url:verify-domains';

    protected $description = 'Re-check DNS verification for every non-disabled custom domain.';

    public function handle(): int
    {
        CustomDomain::query()
            ->whereNull('disabled_at')
            ->pluck('id')
            ->each(fn (int $id) => VerifyDomainJob::dispatch($id));

        return self::SUCCESS;
    }
}
