<?php

namespace Aura\Base\Contracts;

interface ContextualFieldProvider extends FieldProvider
{
    /**
     * Declare the complete context-independent union of field slugs managed by
     * this provider for the resource.
     *
     * @param  class-string<DefinesFields>  $resourceClass
     * @return array<int, string>
     */
    public function managedFieldSlugs(string $resourceClass): array;
}
