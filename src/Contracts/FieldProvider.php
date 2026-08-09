<?php

namespace Aura\Base\Contracts;

use Aura\Base\FieldProviderContext;

interface FieldProvider
{
    /**
     * Declare every runtime dimension that may change the provided fields.
     *
     * @param  class-string<DefinesFields>  $resourceClass
     * @return array<string, bool|float|int|string|null>
     */
    public function cacheContext(string $resourceClass): array;

    public function cacheVersion(FieldProviderContext $context): string|int;

    /**
     * @return array<array-key, array<string, mixed>>
     */
    public function fields(FieldProviderContext $context): array;
}
