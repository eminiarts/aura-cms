<?php

namespace Aura\Base\Ai;

final readonly class AiConnectionResult
{
    public function __construct(
        public bool $successful,
        public string $message,
        public ?string $response = null,
        public ?int $durationMs = null,
    ) {}

    /** @return array{successful: bool, message: string, response: ?string, duration_ms: ?int} */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'message' => $this->message,
            'response' => $this->response,
            'duration_ms' => $this->durationMs,
        ];
    }
}
