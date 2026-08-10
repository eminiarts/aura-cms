<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;

final class RelationshipFieldFilter implements AppliesFieldFilter
{
    /**
     * @param  array<string, mixed>  $field
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        $context = $capability->context();
        $values = $filter['value'] ?? null;

        if (! $this->isValidPayload($field, $filter, $context, $values)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $qualifiedKeyName = $query->getModel()->getQualifiedKeyName();

        $constraint = function ($subQuery) use ($context, $field, $resource, $values) {
            $subQuery->select($context['owner_pivot_key'])
                ->from('post_relations')
                ->where('post_relations.'.$context['owner_type_column'], $resource->getMorphClass())
                ->where('post_relations.'.$context['value_type_column'], $context['resource_type'])
                ->where('post_relations.slug', $field['slug'])
                ->whereIn('post_relations.'.$context['value_pivot_key'], $values);
        };

        if ($filter['operator'] === 'contains') {
            $query->whereIn($qualifiedKeyName, $constraint);

            return;
        }

        if ($filter['operator'] === 'does_not_contain') {
            $query->whereNotIn($qualifiedKeyName, $constraint);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $filter
     * @param  array<string, mixed>  $context
     */
    private function isValidPayload(array $field, array $filter, array $context, mixed $values): bool
    {
        if (! is_string($field['slug'] ?? null)
            || ($filter['name'] ?? null) !== $field['slug']
            || ! in_array($filter['operator'] ?? null, ['contains', 'does_not_contain'], true)
            || ! is_array($values)
            || ! array_is_list($values)
            || $values === []) {
            return false;
        }

        foreach ($values as $value) {
            if ((! is_string($value) || trim($value) === '') && ! is_int($value)) {
                return false;
            }
        }

        foreach (['resource_type', 'owner_pivot_key', 'value_pivot_key', 'owner_type_column', 'value_type_column'] as $key) {
            if (! is_string($context[$key] ?? null) || trim($context[$key]) === '') {
                return false;
            }
        }

        return true;
    }
}
