<?php

namespace Aura\Base\Pipeline;

use Closure;
use InvalidArgumentException;

class AddIdsToFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $parentStack = [];
        $globalTabs = null;
        $lastGlobalTab = null;

        $fields = collect($fields)->values();

        $processedFields = collect();
        $fieldsCount = $fields->count();

        for ($i = 0; $i < $fieldsCount; $i++) {
            $item = $fields[$i];
            $item['_id'] = $i + 1;

            // Handle 'exclude_level' attribute
            $excludeLevel = isset($item['exclude_level']) ? $item['exclude_level'] : 0;
            $shouldExcludeLevels = $excludeLevel > 0;

            if ($shouldExcludeLevels) {
                // Calculate the parent ID by going up $excludeLevel levels in the parent stack
                $parentStackCount = count($parentStack);
                if ($excludeLevel >= $parentStackCount) {
                    // Exclude level is equal to or greater than the stack size, set parent_id to null
                    $item['_parent_id'] = null;
                    // Clear the parent stack
                    $parentStack = [];
                } else {
                    // Set parent_id to the ancestor N levels up
                    $ancestorIndex = $parentStackCount - $excludeLevel - 1;
                    $ancestorItem = $parentStack[$ancestorIndex];
                    $item['_parent_id'] = $ancestorItem['_id'];
                    // Adjust the parent stack to this level
                    $parentStack = array_slice($parentStack, 0, $ancestorIndex + 1);
                }

                if ($item['field']->group === true) {
                    // Since this is a group, push it onto the stack
                    $parentStack[] = $item;
                }

                $processedFields[] = $item;
                continue;
            }

            // Handle global fields
            if (optional($item)['global'] === true) {
                if ($item['field']->type == 'tabs') {
                    $globalTabs = $item;
                } elseif ($item['field']->type == 'tab') {
                    $lastGlobalTab = $item;
                }

                $item['_parent_id'] = $globalTabs ? $globalTabs['_id'] : null;
                // Reset the parent stack to only include the current global item
                $parentStack = [$item];

                $processedFields[] = $item;
                continue;
            }

            // Handle group fields (e.g., panels, tabs)
            if ($item['field']->group === true) {
                // **New Logic to Handle Sibling Tabs**
                if ($item['field']->type == 'tab') {
                    // Pop previous tab if it's at the same level
                    $lastItem = end($parentStack);
                    if ($lastItem && $lastItem['field']->type == 'tab') {
                        array_pop($parentStack);
                    }
                }

                // Set parent ID
                $currentParent = end($parentStack);
                $item['_parent_id'] = $currentParent ? $currentParent['_id'] : null;

                // Push to parent stack
                $parentStack[] = $item;
            } else {
                // Regular field
                $currentParent = end($parentStack);
                $item['_parent_id'] = $currentParent ? $currentParent['_id'] : null;
            }

            $processedFields[] = $item;

            // Optional: Additional logic to manage the parent stack
            // End of the list or next item handling can be added here if necessary
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

        // ray($processedFields);

        return $next($processedFields);
    }
}
