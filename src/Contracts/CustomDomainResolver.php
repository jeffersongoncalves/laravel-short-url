<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

/**
 * Resolves the incoming request host to a CustomDomain for host apps that
 * already maintain their own domain→tenant mapping and don't want to
 * duplicate it into short_url_custom_domains. Bind an implementation in
 * your own ServiceProvider::register() — see Pipeline\Stages\ResolveHost.
 *
 * The returned CustomDomain doesn't need to be persisted: build a
 * transient instance (id, tenant_id, root_redirect_url as needed) from
 * your own domain data. Checked before the package's own CustomDomain
 * table lookup, and only when short-url.domains.enabled is true.
 */
interface CustomDomainResolver
{
    public function resolve(string $host): ?CustomDomain;
}
