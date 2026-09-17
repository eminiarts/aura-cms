<?php

namespace Aura\Base\Ai;

use Aura\Base\Settings\SettingsStore;
use InvalidArgumentException;

final readonly class AiConfigurationRepository
{
    public function __construct(private SettingsStore $settings) {}

    public function get(): AiConfiguration
    {
        $settings = $this->settings->values();
        $providerValue = $settings['ai-provider'] ?? config('aura.ai.provider', AiProvider::OpenAi->value);
        $provider = AiProvider::tryFrom((string) $providerValue);

        if (! $provider) {
            throw new InvalidArgumentException("AI provider [{$providerValue}] is not supported.");
        }

        $providerDefaults = config('aura.ai.providers.'.$provider->value, []);
        $endpoint = $this->firstFilled(
            $settings['ai-endpoint'] ?? null,
            config('aura.ai.endpoint'),
            $providerDefaults['endpoint'] ?? null,
        );
        $model = $this->firstFilled(
            $settings['ai-model'] ?? null,
            config('aura.ai.model'),
            $providerDefaults['model'] ?? null,
        );
        $apiKey = $this->settings->secret('ai-api-key', $settings)
            ?? config('aura.ai.api_key');

        if (! is_string($endpoint) || trim($endpoint) === '') {
            throw new InvalidArgumentException("AI provider [{$provider->label()}] requires an endpoint.");
        }

        if (! is_string($model) || trim($model) === '') {
            throw new InvalidArgumentException("AI provider [{$provider->label()}] requires a model.");
        }

        if ($provider->requiresApiKey() && (! is_string($apiKey) || $apiKey === '')) {
            throw new InvalidArgumentException("AI provider [{$provider->label()}] requires an API key.");
        }

        return new AiConfiguration(
            provider: $provider,
            endpoint: rtrim($endpoint, '/'),
            model: $model,
            apiKey: is_string($apiKey) && $apiKey !== '' ? $apiKey : null,
            timeout: max(1, (int) config('aura.ai.timeout', 30)),
        );
    }

    private function firstFilled(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
