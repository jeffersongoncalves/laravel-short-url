<?php

use JeffersonGoncalves\LaravelShortUrl\Dns\NativeDnsVerifier;

it('fails verification for a domain with no matching dns records', function () {
    $result = (new NativeDnsVerifier)->verify('this-domain-should-not-resolve.invalid', 'some-token');

    expect($result->verified)->toBeFalse()
        ->and($result->error)->not->toBeNull();
});

it('never throws even for a malformed domain', function () {
    $result = (new NativeDnsVerifier)->verify('', 'token');

    expect($result->verified)->toBeFalse();
});
