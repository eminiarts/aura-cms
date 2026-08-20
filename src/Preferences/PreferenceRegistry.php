<?php

namespace Aura\Base\Preferences;

use InvalidArgumentException;

final class PreferenceRegistry
{
    /** @var array<string, PreferenceDefinition> */
    private array $definitions = [];

    /** @return array<string, PreferenceDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    public function get(string $key): PreferenceDefinition
    {
        return $this->definitions[$key]
            ?? throw new InvalidArgumentException("Preference [{$key}] is not registered.");
    }

    public function register(PreferenceDefinition $definition): self
    {
        if (isset($this->definitions[$definition->key])) {
            throw new InvalidArgumentException("Preference [{$definition->key}] is already registered.");
        }

        $this->definitions[$definition->key] = $definition;

        return $this;
    }
}
