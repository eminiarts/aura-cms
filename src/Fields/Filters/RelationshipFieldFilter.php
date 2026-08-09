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
        $values = is_array($filter['value'] ?? null) ? array_values($filter['value']) : [];
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
}
