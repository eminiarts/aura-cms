<?php

namespace Aura\Base\Settings;

use Aura\Base\Ai\AiProvider;
use Aura\Base\Livewire\Settings;

final class CoreSettingsPages
{
    /** @return list<SettingsPage> */
    public static function all(): array
    {
        return [
            new SettingsPage(
                slug: 'general',
                title: 'General',
                fields: Settings::generalFields(),
                icon: 'cog',
                order: 0,
            ),
            new SettingsPage(
                slug: 'ai',
                title: 'AI',
                fields: self::aiFields(),
                icon: 'config',
                description: 'Configure the shared AI provider used by Aura and installed plugins.',
                order: 50,
                defaults: [
                    'ai-provider' => config('aura.ai.provider', AiProvider::OpenAi->value),
                    'ai-endpoint' => config('aura.ai.endpoint', ''),
                    'ai-model' => config('aura.ai.model', ''),
                ],
                secretFields: ['ai-api-key'],
                secretContexts: ['ai-api-key' => 'ai-provider'],
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function aiFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'AI Connection',
                'slug' => 'panel-ai-connection',
            ],
            [
                'name' => 'Provider',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'ai-provider',
                'validation' => 'required|in:openai,anthropic,gemini,glm,custom',
                'options' => [
                    AiProvider::OpenAi->value => AiProvider::OpenAi->label(),
                    AiProvider::Anthropic->value => AiProvider::Anthropic->label(),
                    AiProvider::Gemini->value => AiProvider::Gemini->label(),
                    AiProvider::Glm->value => AiProvider::Glm->label(),
                    AiProvider::Custom->value => AiProvider::Custom->label(),
                ],
                'style' => ['width' => '50'],
            ],
            [
                'name' => 'Model',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'ai-model',
                'validation' => 'nullable|string|max:255',
                'instructions' => 'Leave blank to use the configured default for the selected provider.',
                'style' => ['width' => '50'],
            ],
            [
                'name' => 'Endpoint',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'ai-endpoint',
                'validation' => 'nullable|string|max:2048',
                'instructions' => 'Leave blank for the provider default, or enter an OpenAI-compatible cloud or local base URL.',
                'style' => ['width' => '50'],
            ],
            [
                'name' => 'API Key',
                'type' => 'Aura\\Base\\Fields\\Password',
                'slug' => 'ai-api-key',
                'validation' => 'nullable|string|max:4096',
                'instructions' => 'Stored encrypted. Leave blank to keep the configured key. Custom local endpoints may omit it.',
                'style' => ['width' => '50'],
            ],
            [
                'name' => 'Test AI connection',
                'type' => 'Aura\\Base\\Fields\\View',
                'slug' => 'ai-connection-test',
                'view' => 'aura::settings.ai-connection-test',
            ],
        ];
    }
}
