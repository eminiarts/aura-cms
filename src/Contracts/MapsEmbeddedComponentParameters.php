<?php

namespace Aura\Base\Contracts;

use Aura\Base\Services\EmbeddedComponentContext;

interface MapsEmbeddedComponentParameters
{
    /**
     * Return only null, scalar, or nested array values. Eloquent models,
     * collections, closures, and arbitrary objects are rejected.
     *
     * @return array<string, mixed>
     */
    public function map(EmbeddedComponentContext $context): array;
}
