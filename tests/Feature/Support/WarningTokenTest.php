<?php

use JeffersonGoncalves\LaravelShortUrl\Support\WarningToken;

it('generates a token that validates for the same url key', function () {
    $token = WarningToken::generate('abc1234');

    expect(WarningToken::isValid('abc1234', $token))->toBeTrue();
});

it('rejects a token generated for a different url key', function () {
    $token = WarningToken::generate('abc1234');

    expect(WarningToken::isValid('other999', $token))->toBeFalse();
});

it('rejects a missing or malformed token', function () {
    expect(WarningToken::isValid('abc1234', null))->toBeFalse()
        ->and(WarningToken::isValid('abc1234', 'not-a-token'))->toBeFalse();
});

it('rejects an expired token', function () {
    config(['short-url.security.warning.token_ttl_minutes' => 5]);
    $token = WarningToken::generate('abc1234');

    $this->travel(6)->minutes();

    expect(WarningToken::isValid('abc1234', $token))->toBeFalse();
});
