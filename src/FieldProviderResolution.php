<?php

namespace Aura\Base;

final readonly class FieldProviderResolution
{
    /**
     * @param  array<array-key, array<string, mixed>>  $fields
     */
    public function __construct(
        public string $cacheKey,
        public array $fields,
    ) {}
}
