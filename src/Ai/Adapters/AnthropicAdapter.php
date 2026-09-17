<?php

namespace Aura\Base\Ai\Adapters;

use Aura\Base\Ai\AiConfiguration;
use Aura\Base\Ai\AiProviderException;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class AnthropicAdapter
{
    public function __construct(private Factory $http) {}

    public function generate(AiConfiguration $configuration, string $prompt, ?string $systemPrompt = null): string
    {
        $payload = [
            'model' => $configuration->model,
            'max_tokens' => 1024,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        if (filled($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        $response = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($configuration->timeout)
            ->withHeaders([
                'anthropic-version' => '2023-06-01',
                'x-api-key' => $configuration->apiKey,
            ])
            ->post($this->messagesUrl($configuration->endpoint), $payload);

        if ($response->failed()) {
            throw AiProviderException::fromResponse($configuration, $response);
        }

        $content = $response->json('content.0.text');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Anthropic Claude returned no generated text.');
        }

        return trim($content);
    }

    private function messagesUrl(string $endpoint): string
    {
        if (str_ends_with($endpoint, '/messages')) {
            return $endpoint;
        }

        return rtrim($endpoint, '/').'/messages';
    }
}
