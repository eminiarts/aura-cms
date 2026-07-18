<?php

namespace Aura\Base\Contracts;

/**
 * Contract for the load-bearing field-definition promise every Aura resource
 * makes. `getFields()` returns the ordered list of field definitions that the
 * resource, table, and form layers consume.
 *
 * Declared without a return type on purpose: the base
 * AuraModelConfig::getFields() is untyped and every host-app resource omits the
 * return type too, so a typed interface method would fatal existing resources
 * at class-load. Implementations MAY add `: array`; the PHPDoc pins the shape.
 */
interface DefinesFields
{
    /**
     * @return array<array-key,array<string,mixed>>
     */
    public static function getFields();
}
