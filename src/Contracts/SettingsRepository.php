<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

interface SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function forget(string $key): void;

    /**
     * Declarative schema of known settings, consumed by the plugin to build
     * its settings UI without duplicating knowledge of what each key means.
     *
     * @return array<string, array{type: string, default: mixed, label: string, group: string, rules: array<int, string>}>
     */
    public function schema(): array;
}
