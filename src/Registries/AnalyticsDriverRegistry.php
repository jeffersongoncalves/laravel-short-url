<?php

namespace JeffersonGoncalves\LaravelShortUrl\Registries;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;

class AnalyticsDriverRegistry
{
    /**
     * @var array<string, Closure(): AnalyticsDriver>
     */
    protected array $factories = [];

    public function extend(string $name, Closure $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function driver(string $name): ?AnalyticsDriver
    {
        return isset($this->factories[$name]) ? call_user_func($this->factories[$name]) : null;
    }

    /**
     * Drivers turned on via short-url.analytics.{name}.enabled.
     *
     * @return array<int, AnalyticsDriver>
     */
    public function enabledDrivers(): array
    {
        $drivers = [];

        foreach (array_keys($this->factories) as $name) {
            if (config("short-url.analytics.{$name}.enabled", false)) {
                $driver = $this->driver($name);

                if ($driver) {
                    $drivers[] = $driver;
                }
            }
        }

        return $drivers;
    }
}
