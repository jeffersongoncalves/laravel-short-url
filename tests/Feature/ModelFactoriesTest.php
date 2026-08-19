<?php

use JeffersonGoncalves\LaravelShortUrl\Models\Alert;
use JeffersonGoncalves\LaravelShortUrl\Models\ApiKey;
use JeffersonGoncalves\LaravelShortUrl\Models\AuditLog;
use JeffersonGoncalves\LaravelShortUrl\Models\BioLink;
use JeffersonGoncalves\LaravelShortUrl\Models\BioPage;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\Folder;
use JeffersonGoncalves\LaravelShortUrl\Models\Pixel;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Tag;
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;
use JeffersonGoncalves\LaravelShortUrl\Models\WebhookDelivery;

// Regression test: every HasFactory model here relies on newFactory() to
// resolve its factory class — a global Factory::guessFactoryNamesUsing()
// override only ever worked inside this package's own test suite (it lived
// in tests/TestCase.php), so a real consumer (a Filament plugin, a seeder)
// got "Call to undefined method X::factory()" for any model missing this.
// This test creates one of every factory-backed model to catch the next one.
it('creates every factory-backed model without error', function (string $model) {
    expect($model::factory()->create())->toBeInstanceOf($model);
})->with([
    ShortUrl::class,
    CustomDomain::class,
    Folder::class,
    Tag::class,
    UtmTemplate::class,
    Pixel::class,
    Webhook::class,
    BioPage::class,
    BioLink::class,
    ApiKey::class,
    Visit::class,
    Conversion::class,
    Alert::class,
    AuditLog::class,
    WebhookDelivery::class,
]);
