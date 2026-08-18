<?php

use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;

it('exposes its utm fields as an attributes array', function () {
    $template = UtmTemplate::factory()->create([
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
        'utm_campaign' => 'spring-sale',
        'utm_term' => null,
        'utm_content' => null,
    ]);

    expect($template->toUtmAttributes())->toBe([
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
        'utm_campaign' => 'spring-sale',
        'utm_term' => null,
        'utm_content' => null,
    ]);
});
