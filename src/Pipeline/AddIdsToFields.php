<?php

namespace Aura\Base\Pipeline;

use Closure;
use InvalidArgumentException;

class AddIdsToFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $currentParent = null;
        $globalTabs = null;
        $parentPanel = null;
        $parentTab = null;

        $fields = collect($fields)->values()->map(function ($item, $key) use (&$currentParent, &$globalTabs, &$parentPanel, &$parentTab, &$fields) {
            $item['_id'] = $key + 1;

            $shouldNotBeNested = ! empty(optional($item)['exclude_from_nesting']) && $item['exclude_from_nesting'] === true;

            if ($shouldNotBeNested) {
                // Set the parent ID to the one before the current parent if it's set, or null
                $item['_parent_id'] = $currentParent ? $currentParent['_parent_id'] : null;

                if ($item['field']->type === 'tab' && $currentParent['field']->type === 'panel') {
                    $item['_parent_id'] = $item['_parent_id'] - 1;
                }

                if ($item['field']->group === true) {
                    $currentParent = $item;
                }

                // Try
                $parentPanel = false;
                $currentParent = $item;

                return $item;
            }

            if (optional($item)['global'] === true && ! $globalTabs) {
                if ($item['field']->type == 'tabs') {
                    $globalTabs = $item;
                }

                $item['_parent_id'] = null;
                $currentParent = $item;
                $parentPanel = null;

                return $item;
            }

            if ($item['field']->type !== 'panel' && $item['field']->group === true) {
                if (optional($item)['global']) {
                    // If type = group
                    if ($item['field']->type === 'group') {
                        $item['_parent_id'] = $currentParent['_parent_id'];
                        $currentParent = $item;
                        $parentPanel = null;
                    } else {
                        $item['_parent_id'] = optional($globalTabs)['_id'];
                        $parentPanel = null;
                    }
                }
                // Same Level Grouping
                elseif (optional($currentParent)['type'] == $item['type']) {
                    // Easier Option for now, should refactor
                    $item['_parent_id'] = $currentParent['_parent_id'];
                }
                // Parent Tab
                elseif ($item['field']->type == 'tab') {
                    if ($parentTab) {
                        $item['_parent_id'] = $parentTab['_parent_id'];
                    } else {
                        $item['_parent_id'] = optional($currentParent)['_id'];
                    }
                    // ray('new tab', $item, $parentTab)->red();
                    $parentTab = $item;
                }
                // Nested False
                elseif (optional($item)['nested'] === false) {
                    // dd($item);
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                // If Tab is set to Global, set it to GlobalTabs
                else {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                if (optional($item)['nested'] === true) {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                $currentParent = $item;
            } elseif ($item['field']->type !== 'panel' && $item['field']->group === false) {
                $item['_parent_id'] = optional($currentParent)['_id'];
            } elseif ($item['field']->type == 'panel') {

                if (optional($item)['global']) {
                    $item['_parent_id'] = null;
                    $parentPanel = null;
                    $currentParent = null;
                }

                if ($parentTab) {
                    $item['_parent_id'] = $parentTab['_id'];
                    $parentPanel = $item;
                    $parentTab = null;
                    $currentParent = $item;

                    return $item;
                }

                if ($parentPanel) {
                    $item['_parent_id'] = $parentPanel['_parent_id'];
                } else {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                $currentParent = $item;
                $parentPanel = $item;
                $parentTab = null;
            } else {

                throw new InvalidArgumentException('Unexpected field configuration.');
            }

            return $item;
        });

        return $next($fields);
    }
}
