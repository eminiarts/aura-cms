<?php

namespace Aura\Base\Pipeline;

use Closure;

class AddIdsToFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        if (request()->url() != 'http://aura-demo.test') {
            // ray('before:', $fields->toJson())->green();
        }

        $parentStack = [];
        $globalTabs = null;

        $fields = collect($fields)->values();

        $processedFields = collect();
        $fieldsCount = $fields->count();

        // Keep track of group field IDs by type
        $groupFieldIdsByType = [];

        for ($i = 0; $i < $fieldsCount; $i++) {
            $item = $fields[$i];
            $item['_id'] = $i + 1;

            // Ensure 'type' is set
            if (!isset($item['type'])) {
                $item['type'] = $item['field']->type ?? null;
            }

            // Handle 'exclude_level' attribute
            $excludeLevel = isset($item['exclude_level']) ? $item['exclude_level'] : 0;
            $shouldExcludeLevels = $excludeLevel > 0;

            if ($shouldExcludeLevels) {
                // Existing logic...
                // ...
                $processedFields[] = $item;
                continue;
            }

            // Handle global fields
            if (optional($item)['global'] === true) {
                if ($item['field']->type == 'tabs') {
                    $globalTabs = $item;
                }

                $item['_parent_id'] = $globalTabs ? $globalTabs['_id'] : null;
                // Reset the parent stack to only include the current global item
                $parentStack = [$item];

                $processedFields[] = $item;
                continue;
            }

            // Handle group fields (e.g., panels, tabs)
            if ($item['field']->group === true) {
                if (
                    isset($item['field']->sameLevelGrouping) &&
                    $item['field']->sameLevelGrouping === true &&
                    isset($item['field']->wrapper)
                ) {
                    // sameLevelGrouping is true and wrapper is set
                    // Try to find the wrapper in the groupFieldIdsByType
                    $wrapperType = $item['field']->wrapper;

                    if (isset($groupFieldIdsByType[$wrapperType])) {
                        // Set '_parent_id' to '_id' of the wrapper
                        $item['_parent_id'] = $groupFieldIdsByType[$wrapperType];
                    } else {
                        // Wrapper not found, set '_parent_id' to null or handle appropriately
                        // Since it should not fail if wrapper not found
                        $item['_parent_id'] = null;
                    }

                    // Push current field onto parentStack
                    $parentStack[] = $item;

                    // Add this group's _id to groupFieldIdsByType
                    $groupFieldIdsByType[$item['type']] = $item['_id'];
                } else {
                    // Regular group field
                    // Set '_parent_id' to current parent (end of parentStack)
                    $currentParent = end($parentStack);
                    $item['_parent_id'] = $currentParent ? $currentParent['_id'] : null;

                    // Push current field onto parentStack
                    $parentStack[] = $item;

                    // Add this group's _id to groupFieldIdsByType
                    $groupFieldIdsByType[$item['type']] = $item['_id'];
                }
            } else {
                // Regular field
                $currentParent = end($parentStack);
                $item['_parent_id'] = $currentParent ? $currentParent['_id'] : null;
            }

            $processedFields[] = $item;
        }

        // Ensure no cycles in parent IDs
        $idMap = $processedFields->pluck('_id')->all();

        $processedFields = $processedFields->transform(function ($field) use ($idMap) {
            if ($field['_parent_id'] === $field['_id']) {
                $field['_parent_id'] = null;
            }
            // Also ensure _parent_id exists in idMap
            if ($field['_parent_id'] && !in_array($field['_parent_id'], $idMap)) {
                $field['_parent_id'] = null;
            }
            return $field;
        });

        if (request()->url() != 'http://aura-demo.test') {
            // ray('after:', $processedFields->toJson())->blue();
            // ray(request()->url())->blue();
        }

        return $next($processedFields);
    }
}
