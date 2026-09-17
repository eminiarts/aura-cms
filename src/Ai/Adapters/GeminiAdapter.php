<?php

namespace Aura\Base\Ai\Adapters;

use Aura\Base\Ai\AiConfiguration;
use Aura\Base\Ai\AiProviderException;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class GeminiAdapter
{
    public function __construct(private Factory $http) {}

    public function generate(AiConfiguration $configuration, string $prompt, ?string $systemPrompt = null): string
    {
        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
        ];

        if (filled($systemPrompt)) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        $response = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($configuration->timeout)
            ->withHeaders(['x-goog-api-key' => $configuration->apiKey])
            ->post($this->generateUrl($configuration), $payload);

        if ($response->failed()) {
            throw AiProviderException::fromResponse($configuration, $response);
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Google Gemini returned no generated text.');
        }

        return trim($content);
    }

    private function generateUrl(AiConfiguration $configuration): string
    {
        $model = rawurlencode($configuration->model);

        return rtrim($configuration->endpoint, '/')."/models/{$model}:generateContent";
    }
}
