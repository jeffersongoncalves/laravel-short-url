<?php

it('does not serve the aasa file when disabled', function () {
    config(['short-url.deep_links.aasa.enabled' => false]);

    $this->get('http://short.test/.well-known/apple-app-site-association')->assertNotFound();
});

it('serves the aasa file with configured app ids when enabled', function () {
    config([
        'short-url.deep_links.aasa.enabled' => true,
        'short-url.deep_links.aasa.app_ids' => ['TEAMID.com.example.app'],
    ]);

    $this->get('http://short.test/.well-known/apple-app-site-association')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('applinks.details.0.appID', 'TEAMID.com.example.app');
});

it('does not serve assetlinks.json when disabled', function () {
    config(['short-url.deep_links.assetlinks.enabled' => false]);

    $this->get('http://short.test/.well-known/assetlinks.json')->assertNotFound();
});

it('serves assetlinks.json with configured apps when enabled', function () {
    config([
        'short-url.deep_links.assetlinks.enabled' => true,
        'short-url.deep_links.assetlinks.apps' => [
            ['package' => 'com.example.app', 'fingerprints' => ['AA:BB:CC']],
        ],
    ]);

    $this->get('http://short.test/.well-known/assetlinks.json')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('0.target.package_name', 'com.example.app');
});
