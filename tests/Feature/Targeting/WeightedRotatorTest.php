<?php

use JeffersonGoncalves\LaravelShortUrl\Targeting\WeightedRotator;

it('returns null for an empty variant list', function () {
    expect(WeightedRotator::pick([]))->toBeNull();
});

it('picks the only variant when there is one', function () {
    $variant = WeightedRotator::pick([['url' => 'https://a.test', 'weight' => 100]]);

    expect($variant['url'])->toBe('https://a.test');
});

it('always picks the same variant for the same sticky key', function () {
    $variants = [
        ['url' => 'https://a.test', 'weight' => 50],
        ['url' => 'https://b.test', 'weight' => 50],
    ];

    $first = WeightedRotator::pick($variants, 'visitor-123');
    $second = WeightedRotator::pick($variants, 'visitor-123');

    expect($first['url'])->toBe($second['url']);
});

it('only ever picks from the given variants over many rolls', function () {
    $variants = [
        ['url' => 'https://a.test', 'weight' => 30],
        ['url' => 'https://b.test', 'weight' => 70],
    ];

    $urls = collect(range(1, 50))->map(fn () => WeightedRotator::pick($variants)['url'])->unique();

    expect($urls->diff(['https://a.test', 'https://b.test']))->toBeEmpty();
});

it('falls back to the first variant when all weights are zero', function () {
    $variants = [
        ['url' => 'https://a.test', 'weight' => 0],
        ['url' => 'https://b.test', 'weight' => 0],
    ];

    expect(WeightedRotator::pick($variants)['url'])->toBe('https://a.test');
});
