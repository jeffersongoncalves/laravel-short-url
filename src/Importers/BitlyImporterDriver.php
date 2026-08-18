<?php

namespace JeffersonGoncalves\LaravelShortUrl\Importers;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ImporterDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\ImportPreview;
use JeffersonGoncalves\LaravelShortUrl\Data\ImportReport;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;
use Throwable;

/**
 * Bitly API v4 import. $source is the Bitly group GUID; the access token
 * comes from short-url.importers.bitly.access_token. Reference
 * implementation for the "$provider API importer" shape — Rebrandly,
 * Dub, Short.io and TinyURL follow the same preview()/import() split
 * against their own list endpoints.
 */
class BitlyImporterDriver implements ImporterDriver
{
    protected const PER_PAGE = 50;

    public function __construct(protected ShortUrlManager $manager) {}

    public function preview(string $source): ImportPreview
    {
        $page = $this->fetchPage($source, 1);
        $links = $page['links'];

        return new ImportPreview(
            totalRows: $page['total'],
            sampleRows: array_slice($links, 0, 5),
            columns: ['long_url', 'id', 'title'],
            warnings: $this->hasAccessToken() ? [] : ['short-url.importers.bitly.access_token is not configured.'],
        );
    }

    public function import(string $source): ImportReport
    {
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $page = 1;

        do {
            $result = $this->fetchPage($source, $page);
            $links = $result['links'];

            foreach ($links as $link) {
                $destinationUrl = $link['long_url'] ?? null;

                if (! $destinationUrl) {
                    $skipped++;

                    continue;
                }

                try {
                    $this->manager->create(array_filter([
                        'destination_url' => $destinationUrl,
                        'title' => $link['title'] ?? null,
                    ]));
                    $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = ($link['id'] ?? 'unknown').': '.$e->getMessage();
                }
            }

            $page++;
            $hasNext = ! empty($result['next_page']);
        } while ($hasNext && $links !== []);

        return new ImportReport($imported, $skipped, $failed, $errors);
    }

    /**
     * @return array{links: array<int, array<string, mixed>>, total: int, next_page: ?string}
     */
    protected function fetchPage(string $groupGuid, int $page): array
    {
        if (! $this->hasAccessToken()) {
            return ['links' => [], 'total' => 0, 'next_page' => null];
        }

        try {
            $response = Http::withToken((string) config('short-url.importers.bitly.access_token'))
                ->timeout(5)
                ->get('https://api-ssl.bitly.com/v4/groups/'.$groupGuid.'/bitlinks', [
                    'size' => self::PER_PAGE,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                return ['links' => [], 'total' => 0, 'next_page' => null];
            }

            return [
                'links' => $response->json('links', []),
                'total' => $response->json('pagination.total', count($response->json('links', []))),
                'next_page' => $response->json('pagination.next'),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['links' => [], 'total' => 0, 'next_page' => null];
        }
    }

    protected function hasAccessToken(): bool
    {
        return filled(config('short-url.importers.bitly.access_token'));
    }
}
