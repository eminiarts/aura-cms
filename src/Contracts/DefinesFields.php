<?php

namespace Aura\Base\Contracts;

/**
 * Contract for the field-definition promise every Aura resource makes.
 * `getFields()` returns the ordered list of field definitions that the
 * resource, table, and form layers consume.
 */
interface DefinesFields
{
    /**
     * @return array<array-key,array<string,mixed>>
     */
    public static function getFields(): array;
}
