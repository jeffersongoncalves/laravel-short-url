<?php

use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\LaravelShortUrl\LaravelShortUrlServiceProvider;

it('rolls every migration stub back and forward again without error', function () {
    $prefix = config('short-url.table_prefix', 'short_url_');
    $stubsPath = __DIR__.'/../../database/migrations';

    $migrations = array_map(
        fn (string $name) => require $stubsPath.'/'.$name.'.php.stub',
        LaravelShortUrlServiceProvider::MIGRATIONS
    );

    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }

    expect(Schema::hasTable($prefix.'urls'))->toBeFalse();

    foreach ($migrations as $migration) {
        $migration->up();
    }

    expect(Schema::hasTable($prefix.'urls'))->toBeTrue()
        ->and(Schema::hasTable($prefix.'visits'))->toBeTrue()
        ->and(Schema::hasTable($prefix.'daily_stats'))->toBeTrue();
});
