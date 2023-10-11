<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyParentConditionalLogic implements Pipe
{
    public function handle($fields, Closure $next)
    {
        // Build a map of field IDs to parent IDs
        $parentIdMap = $fields->pluck('_parent_id', '_id');

        // Build a map of field IDs to conditional logics
        $conditionalLogicMap = $fields->pluck('conditional_logic', '_id');

        // Process fields
        $fields = $fields->map(function ($field) use ($parentIdMap, $conditionalLogicMap) {
            $parentIds = $this->getParentIds($parentIdMap, $field['_id']);

            // Merge Conditional Logic of parent IDs with current conditional Logic
            $parentConditionalLogics = collect($parentIds)
                ->map(fn ($id) => $conditionalLogicMap[$id] ?? [])
                ->filter()
                ->flatten(1);

            $field['conditional_logic'] = $parentConditionalLogics
                ->merge($field['conditional_logic'] ?? [])
                ->toArray();

            return $field;
        });

        return $next($fields);
    }

    public function getParentIds($parentIdMap, $id): array
    {
        $parentIds = [];
        $currentId = $id;

        while (isset($parentIdMap[$currentId])) {
            $currentId = $parentIdMap[$currentId];
            $parentIds[] = $currentId;
        }

        return $parentIds;
    }
}
