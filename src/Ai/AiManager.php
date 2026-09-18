<?php

namespace Aura\Base\Ai;

use Aura\Base\Ai\Adapters\AnthropicAdapter;
use Aura\Base\Ai\Adapters\GeminiAdapter;
use Aura\Base\Ai\Adapters\OpenAiCompatibleAdapter;
use Aura\Base\Contracts\AiConnector;
use Throwable;

final readonly class AiManager implements AiConnector
{
    public function __construct(
        private AiConfigurationRepository $configuration,
        private AnthropicAdapter $anthropic,
        private GeminiAdapter $gemini,
        private OpenAiCompatibleAdapter $openAiCompatible,
    ) {}

    public function generate(string $prompt, ?string $systemPrompt = null): string
    {
        $configuration = $this->configuration->get();

        return match ($configuration->provider) {
            AiProvider::Anthropic => $this->anthropic->generate($configuration, $prompt, $systemPrompt),
            AiProvider::Gemini => $this->gemini->generate($configuration, $prompt, $systemPrompt),
            AiProvider::Custom, AiProvider::Glm, AiProvider::OpenAi => $this->openAiCompatible->generate($configuration, $prompt, $systemPrompt),
        };
    }

    public function testConnection(): AiConnectionResult
    {
        $startedAt = hrtime(true);
        $configuration = null;

        try {
            $configuration = $this->configuration->get();
            $response = $this->generate(
                'Reply with exactly: OK',
                'This is a connection test. Follow the user instruction without additional text.',
            );
            $duration = (int) round((hrtime(true) - $startedAt) / 1_000_000);

            return new AiConnectionResult(
                successful: true,
                message: "Connected to {$configuration->provider->label()} in {$duration} ms.",
                response: $response,
                durationMs: $duration,
            );
        } catch (Throwable $exception) {
            $message = $exception->getMessage();

            if ($configuration?->apiKey) {
                $message = str_replace(
                    [$configuration->apiKey, urlencode($configuration->apiKey)],
                    '[redacted]',
                    $message,
                );
            }

            return new AiConnectionResult(
                successful: false,
                message: 'AI connection failed: '.$message,
                durationMs: (int) round((hrtime(true) - $startedAt) / 1_000_000),
            );
        }
    }
}
