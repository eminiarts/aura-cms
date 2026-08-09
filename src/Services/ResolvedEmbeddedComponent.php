<?php

namespace Aura\Base\Services;

final readonly class ResolvedEmbeddedComponent
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $alias,
        public array $parameters,
        public string $key,
    ) {}
}
