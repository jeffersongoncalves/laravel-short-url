<?php

use JeffersonGoncalves\LaravelShortUrl\Models\BioLink;
use JeffersonGoncalves\LaravelShortUrl\Models\BioPage;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

beforeEach(function () {
    config(['short-url.bio.enabled' => true]);
});

it('returns 404 when link-in-bio is disabled', function () {
    config(['short-url.bio.enabled' => false]);
    BioPage::factory()->create(['handle' => 'jeff']);

    $this->get('http://short.test/bio/jeff')->assertNotFound();
});

it('renders a published bio page and increments total_views', function () {
    $page = BioPage::factory()->create(['handle' => 'jeff', 'title' => 'Jeff G.']);

    $this->get('http://short.test/bio/jeff')
        ->assertOk()
        ->assertSee('Jeff G.');

    expect($page->refresh()->total_views)->toBe(1);
});

it('returns 404 for an unpublished bio page', function () {
    BioPage::factory()->create(['handle' => 'jeff', 'is_published' => false]);

    $this->get('http://short.test/bio/jeff')->assertNotFound();
});

it('returns 404 for an unknown handle', function () {
    $this->get('http://short.test/bio/does-not-exist')->assertNotFound();
});

it('renders every enabled link and skips disabled ones', function () {
    $page = BioPage::factory()->create(['handle' => 'jeff']);
    BioLink::factory()->create(['bio_page_id' => $page->id, 'label' => 'Visible Link', 'is_enabled' => true]);
    BioLink::factory()->create(['bio_page_id' => $page->id, 'label' => 'Hidden Link', 'is_enabled' => false]);

    $response = $this->get('http://short.test/bio/jeff')->assertOk();

    $response->assertSee('Visible Link')->assertDontSee('Hidden Link');
});

it('redirects and records a click on a plain-url bio link', function () {
    $page = BioPage::factory()->create(['handle' => 'jeff']);
    $link = BioLink::factory()->create([
        'bio_page_id' => $page->id,
        'content' => ['url' => 'https://example.com/target'],
    ]);

    $this->get("http://short.test/bio/jeff/l/{$link->id}")
        ->assertRedirect('https://example.com/target');

    expect($link->refresh()->click_count)->toBe(1);
});

it('redirects through the short url when a bio link is tied to one', function () {
    $page = BioPage::factory()->create(['handle' => 'jeff']);
    $shortUrl = ShortUrl::factory()->create(['url_key' => 'abc1234']);
    $link = BioLink::factory()->create(['bio_page_id' => $page->id, 'short_url_id' => $shortUrl->id]);

    $this->get("http://short.test/bio/jeff/l/{$link->id}")
        ->assertRedirect('http://short.test/abc1234');
});

it('returns 404 when clicking a disabled bio link', function () {
    $page = BioPage::factory()->create(['handle' => 'jeff']);
    $link = BioLink::factory()->create(['bio_page_id' => $page->id, 'is_enabled' => false]);

    $this->get("http://short.test/bio/jeff/l/{$link->id}")->assertNotFound();
});

it('returns 404 when the handle in the url does not match the link\'s page', function () {
    $pageA = BioPage::factory()->create(['handle' => 'page-a']);
    $pageB = BioPage::factory()->create(['handle' => 'page-b']);
    $link = BioLink::factory()->create(['bio_page_id' => $pageA->id]);

    $this->get("http://short.test/bio/{$pageB->handle}/l/{$link->id}")->assertNotFound();
});
