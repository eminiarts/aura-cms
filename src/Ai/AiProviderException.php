<?php

namespace Aura\Base\Ai;

use Illuminate\Http\Client\Response;
use RuntimeException;

final class AiProviderException extends RuntimeException
{
    public static function fromResponse(AiConfiguration $configuration, Response $response): self
    {
        $detail = $response->json('error.message')
            ?? $response->json('error.status')
            ?? $response->json('message')
            ?? 'The provider returned an unsuccessful response.';
        $detail = is_string($detail) ? $detail : 'The provider returned an unsuccessful response.';

        if ($configuration->apiKey) {
            $detail = str_replace(
                [$configuration->apiKey, urlencode($configuration->apiKey)],
                '[redacted]',
                $detail,
            );
        }

        return new self("{$configuration->provider->label()} returned HTTP {$response->status()}: {$detail}");
    }
}
