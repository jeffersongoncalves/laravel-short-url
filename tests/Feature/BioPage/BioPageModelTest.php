<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Models\BioLink;
use JeffersonGoncalves\LaravelShortUrl\Models\BioPage;

it('orders links by position', function () {
    $page = BioPage::factory()->create();
    BioLink::factory()->create(['bio_page_id' => $page->id, 'label' => 'Second', 'position' => 2]);
    BioLink::factory()->create(['bio_page_id' => $page->id, 'label' => 'First', 'position' => 1]);

    expect($page->links->pluck('label')->all())->toBe(['First', 'Second']);
});

it('only includes enabled links in enabledLinks', function () {
    $page = BioPage::factory()->create();
    BioLink::factory()->create(['bio_page_id' => $page->id, 'is_enabled' => true]);
    BioLink::factory()->create(['bio_page_id' => $page->id, 'is_enabled' => false]);

    expect($page->enabledLinks)->toHaveCount(1);
});

it('increments click_count via recordClick', function () {
    $page = BioPage::factory()->create();
    $link = BioLink::factory()->create(['bio_page_id' => $page->id, 'click_count' => 0]);

    $link->recordClick();

    expect($link->refresh()->click_count)->toBe(1);
});

it('enforces a unique handle', function () {
    BioPage::factory()->create(['handle' => 'jeff']);

    // DB::transaction() wraps the failing insert in its own SAVEPOINT —
    // on Postgres, a caught constraint violation still aborts the whole
    // enclosing transaction (including RefreshDatabase's per-test one)
    // until something rolls back to a savepoint.
    expect(fn () => DB::transaction(fn () => BioPage::factory()->create(['handle' => 'jeff'])))
        ->toThrow(QueryException::class);
});
