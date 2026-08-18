<?php

namespace JeffersonGoncalves\LaravelShortUrl\Registries;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ImporterDriver;

class ImporterDriverRegistry
{
    /**
     * @var array<string, Closure(): ImporterDriver>
     */
    protected array $factories = [];

    public function extend(string $name, Closure $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function driver(string $name): ?ImporterDriver
    {
        return isset($this->factories[$name]) ? call_user_func($this->factories[$name]) : null;
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->factories);
    }
}
