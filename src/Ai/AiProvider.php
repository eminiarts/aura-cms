<?php

namespace Aura\Base\Ai;

enum AiProvider: string
{
    public function label(): string
    {
        return match ($this) {
            self::Anthropic => 'Anthropic Claude',
            self::Custom => 'Custom / local endpoint',
            self::Gemini => 'Google Gemini',
            self::Glm => 'GLM',
            self::OpenAi => 'OpenAI',
        };
    }

    public function requiresApiKey(): bool
    {
        return $this !== self::Custom;
    }
    case Anthropic = 'anthropic';
    case Custom = 'custom';
    case Gemini = 'gemini';
    case Glm = 'glm';
    case OpenAi = 'openai';
}
