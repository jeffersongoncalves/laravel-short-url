<?php

namespace JeffersonGoncalves\LaravelShortUrl\Dns;

use JeffersonGoncalves\LaravelShortUrl\Contracts\DnsVerifier;
use JeffersonGoncalves\LaravelShortUrl\Data\VerificationResult;
use Throwable;

/**
 * Verifies domain ownership via, in order: a TXT challenge record, a CNAME
 * pointing back at the app host, or an A record matching this server. Falls
 * back from dns_get_record() to the `dig` binary when the PHP DNS
 * extension isn't available. Never throws — an unverifiable domain is a
 * normal, expected outcome, not an error.
 */
class NativeDnsVerifier implements DnsVerifier
{
    public function verify(string $domain, string $expectedToken): VerificationResult
    {
        try {
            if ($this->txtMatches($domain, $expectedToken)) {
                return new VerificationResult(true, 'txt', now());
            }

            if ($this->cnameMatchesAppHost($domain)) {
                return new VerificationResult(true, 'cname', now());
            }

            if ($this->aRecordMatchesServer($domain)) {
                return new VerificationResult(true, 'a', now());
            }

            return new VerificationResult(false, null, now(), 'No matching DNS record found.');
        } catch (Throwable $e) {
            return new VerificationResult(false, null, now(), $e->getMessage());
        }
    }

    protected function txtMatches(string $domain, string $token): bool
    {
        foreach ($this->lookup('_short-url-verify.'.$domain, 'TXT') as $record) {
            if (str_contains($record, $token)) {
                return true;
            }
        }

        return false;
    }

    protected function cnameMatchesAppHost(string $domain): bool
    {
        $expectedHost = config('short-url.route.domain') ?? parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $expectedHost) {
            return false;
        }

        foreach ($this->lookup($domain, 'CNAME') as $record) {
            if (rtrim(strtolower($record), '.') === strtolower((string) $expectedHost)) {
                return true;
            }
        }

        return false;
    }

    protected function aRecordMatchesServer(string $domain): bool
    {
        $serverIp = $_SERVER['SERVER_ADDR'] ?? null;

        if (! $serverIp) {
            return false;
        }

        return in_array($serverIp, $this->lookup($domain, 'A'), true);
    }

    /**
     * @return array<int, string>
     */
    protected function lookup(string $domain, string $type): array
    {
        if (function_exists('dns_get_record')) {
            $constant = match ($type) {
                'TXT' => DNS_TXT,
                'CNAME' => DNS_CNAME,
                'A' => DNS_A,
                default => DNS_ALL,
            };

            $records = @dns_get_record($domain, $constant);

            if (is_array($records) && $records !== []) {
                return $this->extractValues($records, $type);
            }
        }

        $dig = @shell_exec(sprintf('dig +short %s %s', escapeshellarg($domain), escapeshellarg($type)));

        if ($dig) {
            return array_values(array_filter(array_map('trim', explode("\n", $dig))));
        }

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, string>
     */
    protected function extractValues(array $records, string $type): array
    {
        $key = match ($type) {
            'TXT' => 'txt',
            'CNAME' => 'target',
            'A' => 'ip',
            default => 'ip',
        };

        return array_values(array_filter(array_map(
            fn (array $record) => is_string($record[$key] ?? null) ? $record[$key] : null,
            $records
        )));
    }
}
