<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\LaravelShortUrl\LaravelShortUrlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelShortUrlServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->testing_connection());

        $app['config']->set('short-url.route.domain', 'short.test');
    }

    /**
     * Defaults to an in-memory SQLite connection for local development; CI
     * (tests.yml) sets SHORT_URL_TEST_DB_* to run the same suite against
     * real MySQL and PostgreSQL instances too. Deliberately not the plain
     * DB_* names: Orchestra Testbench itself sets DB_CONNECTION=testing by
     * convention, which would collide with (and always win over) a driver
     * value read from the same variable here.
     *
     * @return array<string, mixed>
     */
    protected function testing_connection(): array
    {
        $driver = env('SHORT_URL_TEST_DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        }

        return [
            'driver' => $driver,
            'host' => env('SHORT_URL_TEST_DB_HOST', '127.0.0.1'),
            'port' => env('SHORT_URL_TEST_DB_PORT'),
            'database' => env('SHORT_URL_TEST_DB_DATABASE', 'testing'),
            'username' => env('SHORT_URL_TEST_DB_USERNAME', 'root'),
            'password' => env('SHORT_URL_TEST_DB_PASSWORD', ''),
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $stubsPath = __DIR__.'/../database/migrations';
        $tempPath = sys_get_temp_dir().'/laravel-short-url-migrations';

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Wipe leftovers from a previous run/naming scheme — loadMigrationsFrom()
        // below loads every file in this directory, stale or not.
        foreach (glob($tempPath.'/*.php') as $leftover) {
            unlink($leftover);
        }

        // Numeric prefixes reproduce LaravelShortUrlServiceProvider::MIGRATIONS
        // order (e.g. bio_pages before bio_links, an FK dependency) — plain
        // stub filenames would sort alphabetically instead and break on any
        // database that enforces foreign keys strictly (MySQL, Postgres).
        foreach (LaravelShortUrlServiceProvider::MIGRATIONS as $index => $migration) {
            $stub = $stubsPath.'/'.$migration.'.php.stub';
            $prefix = str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            copy($stub, $tempPath.'/'.$prefix.'_'.$migration.'.php');
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
