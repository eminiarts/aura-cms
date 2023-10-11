<?php

namespace Eminiarts\Aura\Pipeline;

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

        $fields = collect($fields)->values()->map(function ($item, $key) use (&$currentParent, &$globalTabs, &$parentPanel, &$parentTab) {
            $item['_id'] = $key + 1;
            $itemField = $item['field'];
            $itemFieldType = $itemField->type;
            $itemFieldGroup = $itemField->group;
            $itemGlobal = optional($item)['global'];
            $itemNested = optional($item)['nested'];

            if ($itemGlobal === true && ! $globalTabs) {
                if ($itemFieldType == 'tabs') {
                    $globalTabs = $item;
                }

                $item['_parent_id'] = null;
                $currentParent = $item;
                $parentPanel = null;

                return $item;
            }

            if ($itemFieldType !== 'panel' && $itemFieldGroup === true) {
                if ($itemGlobal) {
                    if ($itemFieldType === 'group') {
                        $item['_parent_id'] = $currentParent['_parent_id'];
                        $currentParent = $item;
                        $parentPanel = null;
                    } else {
                        $item['_parent_id'] = optional($globalTabs)['_id'];
                        $parentPanel = null;
                    }
                } elseif (optional($currentParent)['type'] == $itemFieldType) {
                    $item['_parent_id'] = $currentParent['_parent_id'];
                } elseif ($itemFieldType == 'tab') {
                    $item['_parent_id'] = $parentTab ? $parentTab['_parent_id'] : optional($currentParent)['_id'];
                    $parentTab = $item;
                } elseif ($itemNested === false) {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                } else {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                if ($itemNested === true) {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                $currentParent = $item;
            } elseif ($itemFieldType !== 'panel' && $itemFieldGroup === false) {
                $item['_parent_id'] = optional($currentParent)['_id'];
            } elseif ($itemFieldType == 'panel') {
                if ($itemGlobal) {
                    $item['_parent_id'] = null;
                    $parentPanel = null;
                    $currentParent = null;
                } else {
                    $item['_parent_id'] = $parentPanel ? $parentPanel['_parent_id'] : optional($currentParent)['_id'];
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
