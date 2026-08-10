<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Fields\Field;
use Aura\Base\Resource;

final class FieldFilterCapabilityResolver
{
    /**
     * @param  array<string, mixed>  $field
     */
    public function resolve(Field $fieldInstance, Resource $model, array $field): FilterCapability
    {
        if ($fieldInstance instanceof ProvidesFilterCapability) {
            return $fieldInstance->provideAuraFilterCapability($model, $field);
        }

        return FilterCapability::text($fieldInstance->filterOptions());
    }
}
