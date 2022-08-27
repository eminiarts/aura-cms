<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyGroupedInputs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $group = 0;
        $groupedFields = false;

        $fields = $fields->map(function ($item, $key) use (&$group, &$groupedFields) {
            // Group Property on the Field Class is responsible for grouping fields
            if ($item['field']->group) {
                $groupedFields = true;
                $group++;
            }

            // Every Field gets a Group if there are no groupedFields yet
            if (! $groupedFields) {
                $group++;
            }

            $item['group'] = $group;

            return $item;
        })
        ->groupBy('group')->map(function ($item, $key) {
            $i = $item->toArray();

            // First item is going to hold all fields under it
            $new = array_shift($i);

            $new['fields'] = $i;

            return $new;
        });

        return  $next($fields);
    }
}
