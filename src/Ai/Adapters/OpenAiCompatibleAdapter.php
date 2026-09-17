<?php

namespace Aura\Base\Ai\Adapters;

use Aura\Base\Ai\AiConfiguration;
use Aura\Base\Ai\AiProviderException;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class OpenAiCompatibleAdapter
{
    public function __construct(private Factory $http) {}

    public function generate(AiConfiguration $configuration, string $prompt, ?string $systemPrompt = null): string
    {
        $messages = [];

        if (filled($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        $request = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($configuration->timeout);

        if ($configuration->apiKey) {
            $request = $request->withToken($configuration->apiKey);
        }

        $response = $request->post($this->completionUrl($configuration->endpoint), [
            'model' => $configuration->model,
            'messages' => $messages,
        ]);

        if ($response->failed()) {
            throw AiProviderException::fromResponse($configuration, $response);
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException("{$configuration->provider->label()} returned no generated text.");
        }

        return trim($content);
    }

    private function completionUrl(string $endpoint): string
    {
        if (str_ends_with($endpoint, '/chat/completions')) {
            return $endpoint;
        }

        return rtrim($endpoint, '/').'/chat/completions';
    }
}
