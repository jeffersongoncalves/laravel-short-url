<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\TargetingResolver;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

function targetingRequest(array $query = [], array $headers = []): Request
{
    $request = Request::create('/abc1234', 'GET', $query);

    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    return $request;
}

it('resolves a single destination unchanged', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'single',
        'destination_url' => 'https://example.com/base',
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest());

    expect($destination->url)->toBe('https://example.com/base');
});

it('picks a split variant', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'split',
        'destination_url' => 'https://example.com/base',
        'rotation_variants' => [
            ['url' => 'https://a.test', 'weight' => 50, 'label' => 'A'],
            ['url' => 'https://b.test', 'weight' => 50, 'label' => 'B'],
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest());

    expect(['https://a.test', 'https://b.test'])->toContain($destination->url)
        ->and($destination->variant)->not->toBeNull();
});

it('falls back to the base url when a rule has no match', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'rules',
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            ['conditions' => [['type' => 'country', 'value' => 'FR']], 'destination' => 'https://example.com/france'],
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest());

    expect($destination->url)->toBe('https://example.com/base')
        ->and($destination->matchedRuleIndex)->toBeNull();
});

it('resolves the first matching rule and reports its index', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'rules',
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            ['conditions' => [['type' => 'utm', 'field' => 'source', 'value' => 'nope']], 'destination' => 'https://example.com/skip'],
            ['conditions' => [['type' => 'utm', 'field' => 'source', 'value' => 'newsletter']], 'destination' => 'https://example.com/matched'],
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest(['utm_source' => 'newsletter']));

    expect($destination->url)->toBe('https://example.com/matched')
        ->and($destination->matchedRuleIndex)->toBe(1);
});

it('supports a rule with a nested split', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'rules',
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            [
                'conditions' => [['type' => 'utm', 'field' => 'source', 'value' => 'newsletter']],
                'split' => [
                    ['url' => 'https://a.test', 'weight' => 100],
                ],
            ],
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest(['utm_source' => 'newsletter']));

    expect($destination->url)->toBe('https://a.test')
        ->and($destination->matchedRuleIndex)->toBe(0);
});

it('adapts a legacy single-rule (non-list) format', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'rules',
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            'conditions' => [['type' => 'utm', 'field' => 'source', 'value' => 'legacy']],
            'destination' => 'https://example.com/legacy',
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest(['utm_source' => 'legacy']));

    expect($destination->url)->toBe('https://example.com/legacy');
});

it('evaluates an "or" match group', function () {
    $shortUrl = ShortUrl::factory()->create([
        'destination_type' => 'rules',
        'destination_url' => 'https://example.com/base',
        'targeting_rules' => [
            [
                'match' => 'or',
                'conditions' => [
                    ['type' => 'utm', 'field' => 'source', 'value' => 'nope'],
                    ['type' => 'utm', 'field' => 'source', 'value' => 'newsletter'],
                ],
                'destination' => 'https://example.com/matched',
            ],
        ],
    ]);

    $destination = app(TargetingResolver::class)->resolve($shortUrl, targetingRequest(['utm_source' => 'newsletter']));

    expect($destination->url)->toBe('https://example.com/matched');
});
