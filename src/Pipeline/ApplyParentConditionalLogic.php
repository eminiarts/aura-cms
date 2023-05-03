<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyParentConditionalLogic implements Pipe
{
    public function getParentIds($fields, $id): array
    {
        $parentIds = [];

        // Find the field with the given id
        $field = $fields->firstWhere('_id', $id);

        // If the field has a parent id, add it to the array and recursively
        // get the parent ids of the parent field
        if ($field['_parent_id'] !== null) {
            $parentIds[] = $field['_parent_id'];
            $parentIds = array_merge($parentIds, $this->getParentIds($fields, $field['_parent_id']));
        }

        return $parentIds;
    }

    public function handle($fields, Closure $next)
    {
        // Foreach $fields as $field, get all parent IDs
        $fields = $fields->map(function ($field) use ($fields) {
            $parentIds = $this->getParentIds($fields, $field['_id']);

            // Merge Conditional Logic of parent IDs with current conditional Logic
            $field['conditional_logic'] = $fields->whereIn('_id', $parentIds)->pluck('conditional_logic')->flatten(1)->filter()->merge(optional($field)['conditional_logic'])->toArray();

            return $field;
        });

        return $next($fields);
    }
}
