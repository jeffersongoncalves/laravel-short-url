<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tenancy;

use JeffersonGoncalves\LaravelShortUrl\Exceptions\PlanLimitExceeded;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * Per-plan usage limits, entirely config-driven — this package has no
 * billing model of its own. A no-op whenever multi-tenancy is off or the
 * current tenant has no plan configured (limit === null means unlimited).
 *
 * short-url.tenancy.plans is a map of plan name => limits; which plan the
 * current tenant is on comes from a host-supplied resolver Closure
 * (short-url.tenancy.plan_resolver), defaulting to the "default" plan.
 */
class PlanLimits
{
    public function __construct(protected TenantContext $tenants) {}

    /**
     * @throws PlanLimitExceeded
     */
    public function assertCanCreateLink(): void
    {
        $limit = $this->limit('links_per_month');

        if ($limit !== null && $this->linksCreatedThisMonth() >= $limit) {
            throw new PlanLimitExceeded('links_per_month', $limit);
        }
    }

    /**
     * @throws PlanLimitExceeded
     */
    public function assertCanCreateDomain(): void
    {
        $limit = $this->limit('domains');

        if ($limit !== null && $this->domainCount() >= $limit) {
            throw new PlanLimitExceeded('domains', $limit);
        }
    }

    public function limit(string $key): ?int
    {
        if (! config('short-url.tenancy.enabled', false)) {
            return null;
        }

        $limits = config("short-url.tenancy.plans.{$this->currentPlan()}", []);

        return $limits[$key] ?? null;
    }

    public function currentPlan(): string
    {
        $resolver = config('short-url.tenancy.plan_resolver');
        $tenantId = $this->tenants->currentId();

        if ($tenantId !== null && is_callable($resolver)) {
            return (string) $resolver($tenantId);
        }

        return 'default';
    }

    protected function linksCreatedThisMonth(): int
    {
        return ShortUrl::query()->where('created_at', '>=', now()->startOfMonth())->count();
    }

    protected function domainCount(): int
    {
        return CustomDomain::query()->count();
    }
}
