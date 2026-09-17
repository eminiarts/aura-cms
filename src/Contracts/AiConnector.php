<?php

namespace Aura\Base\Contracts;

use Aura\Base\Ai\AiConnectionResult;

interface AiConnector
{
    public function generate(string $prompt, ?string $systemPrompt = null): string;

    public function testConnection(): AiConnectionResult;
}
