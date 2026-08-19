<?php

use JeffersonGoncalves\LaravelShortUrl\Support\RefererClassifier;

it('classifies a missing referer as direct', function () {
    expect(RefererClassifier::classify(null, 'short.test'))->toBe('direct');
});

it('classifies a same-host referer as internal', function () {
    expect(RefererClassifier::classify('https://short.test/dashboard', 'short.test'))->toBe('internal');
});

it('classifies known social hosts', function () {
    expect(RefererClassifier::classify('https://www.facebook.com/', 'short.test'))->toBe('social')
        ->and(RefererClassifier::classify('https://t.co/abc', 'short.test'))->toBe('social');
});

it('classifies known search engines', function () {
    expect(RefererClassifier::classify('https://www.google.com/search?q=x', 'short.test'))->toBe('search');
});

it('classifies known webmail hosts as email', function () {
    expect(RefererClassifier::classify('https://mail.google.com/', 'short.test'))->toBe('email');
});

it('classifies an unrecognized external host as direct', function () {
    expect(RefererClassifier::classify('https://some-random-blog.example/', 'short.test'))->toBe('direct');
});
