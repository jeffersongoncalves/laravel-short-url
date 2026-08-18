<?php

use JeffersonGoncalves\LaravelShortUrl\Targeting\SignificanceCalculator;

it('returns null when either sample is empty', function () {
    expect(SignificanceCalculator::zScore(0, 0, 5, 10))->toBeNull();
});

it('returns a zero-ish score for identical proportions', function () {
    $z = SignificanceCalculator::zScore(50, 100, 50, 100);

    expect($z)->toBe(0.0);
});

it('flags a large proportion gap as significant', function () {
    $z = SignificanceCalculator::zScore(90, 100, 10, 100);

    expect(SignificanceCalculator::isSignificant($z))->toBeTrue();
});

it('does not flag a tiny proportion gap as significant', function () {
    $z = SignificanceCalculator::zScore(51, 100, 50, 100);

    expect(SignificanceCalculator::isSignificant($z))->toBeFalse();
});
