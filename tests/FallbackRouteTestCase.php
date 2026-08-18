<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tests;

class FallbackRouteTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('short-url.route.fallback', true);
    }
}
