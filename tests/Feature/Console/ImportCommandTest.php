<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('fails with an unknown driver name', function () {
    $this->artisan('short-url:import', ['driver' => 'nope', 'source' => 'x'])
        ->assertExitCode(1);
});

it('does a dry run without importing anything', function () {
    $path = tempnam(sys_get_temp_dir(), 'short-url-import').'.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, ['destination_url']);
    fputcsv($handle, ['https://a.example']);
    fclose($handle);

    $this->artisan('short-url:import', ['driver' => 'csv', 'source' => $path, '--dry-run' => true])
        ->assertExitCode(0);

    expect(ShortUrl::query()->count())->toBe(0);
});

it('imports rows from a csv file', function () {
    $path = tempnam(sys_get_temp_dir(), 'short-url-import').'.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, ['destination_url']);
    fputcsv($handle, ['https://a.example']);
    fclose($handle);

    $this->artisan('short-url:import', ['driver' => 'csv', 'source' => $path])
        ->assertExitCode(0);

    expect(ShortUrl::query()->count())->toBe(1);
});
