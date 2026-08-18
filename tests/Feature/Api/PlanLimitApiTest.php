<?php

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('returns 403 with a plan_limit_exceeded code when the link limit api call is over the plan limit', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.links_per_month' => 0,
    ]);

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links', ['destination_url' => 'https://example.com'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'plan_limit_exceeded');
});

it('returns 403 with a plan_limit_exceeded code for a domain create call over the plan limit', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.domains' => 0,
    ]);

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/domains', ['domain' => 'links.example.com'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'plan_limit_exceeded');
});
