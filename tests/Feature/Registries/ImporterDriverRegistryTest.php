<?php

use JeffersonGoncalves\LaravelShortUrl\Registries\ImporterDriverRegistry;

it('registers csv and bitly importers on boot', function () {
    $registry = app(ImporterDriverRegistry::class);

    expect($registry->names())->toContain('csv', 'bitly')
        ->and($registry->driver('csv'))->not->toBeNull()
        ->and($registry->driver('unknown'))->toBeNull();
});
