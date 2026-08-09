<?php

namespace Aura\Base;

final readonly class RouteTarget
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $name,
        public array $parameters = [],
    ) {}
}
