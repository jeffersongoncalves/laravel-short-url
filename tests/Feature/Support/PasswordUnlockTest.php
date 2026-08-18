<?php

use JeffersonGoncalves\LaravelShortUrl\Support\PasswordUnlock;

it('is not unlocked before unlock() is called', function () {
    expect(PasswordUnlock::isUnlocked(999))->toBeFalse();
});

it('is unlocked immediately after unlock()', function () {
    PasswordUnlock::unlock(42);

    expect(PasswordUnlock::isUnlocked(42))->toBeTrue();
});

it('is not unlocked once the ttl has passed', function () {
    config(['short-url.security.password.unlock_ttl_minutes' => 10]);
    PasswordUnlock::unlock(7);

    $this->travel(11)->minutes();

    expect(PasswordUnlock::isUnlocked(7))->toBeFalse();
});
