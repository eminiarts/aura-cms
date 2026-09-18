<?php

namespace Aura\Base\Ai;

final readonly class AiConfiguration
{
    public function __construct(
        public AiProvider $provider,
        public string $endpoint,
        public string $model,
        public ?string $apiKey,
        public int $timeout,
    ) {}
}
