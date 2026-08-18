<?php

use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Support\PasswordUnlock;

it('unlocks and redirects back to the short url on a correct password', function () {
    $shortUrl = ShortUrl::factory()->create([
        'url_key' => 'pw123456',
        'password_hash' => Hash::make('secret'),
        'destination_url' => 'https://example.com/secret',
    ]);

    $this->post('http://short.test/pw123456/unlock', ['password' => 'secret'])
        ->assertRedirect('/pw123456');

    expect(PasswordUnlock::isUnlocked($shortUrl->id))->toBeTrue();
});

it('redirects back with an error on a wrong password', function () {
    ShortUrl::factory()->create([
        'url_key' => 'pw123456',
        'password_hash' => Hash::make('secret'),
    ]);

    $this->post('http://short.test/pw123456/unlock', ['password' => 'nope'])
        ->assertSessionHasErrors('password');
});

it('unlocking then visiting the link redirects straight through', function () {
    ShortUrl::factory()->create([
        'url_key' => 'pw123456',
        'password_hash' => Hash::make('secret'),
        'destination_url' => 'https://example.com/secret',
    ]);

    $this->post('http://short.test/pw123456/unlock', ['password' => 'secret']);

    $this->get('http://short.test/pw123456')->assertRedirect('https://example.com/secret');
});

it('shows the password prompt for a locked link', function () {
    ShortUrl::factory()->create([
        'url_key' => 'pw123456',
        'password_hash' => Hash::make('secret'),
    ]);

    $this->get('http://short.test/pw123456')->assertStatus(401);
});
