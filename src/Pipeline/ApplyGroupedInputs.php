<?php

namespace Eminiarts\Aura\Aura\Pipeline;

use Closure;

class ApplyGroupedInputs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $group = 0;
        $groupLevel = '0';
        $groupedFields = false;

        $fields = $fields->map(function ($item, $key) use (&$group, &$groupLevel, &$groupedFields) {
            if ($item['field']->type == 'tab') {
                $group++;
                $groupLevel = strval($group);
            }

            if ($item['field']->type == 'panel') {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // take only first two elements
                $groupLevel = array_slice($groupLevel, 0, 2);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }

            if (! $item['field']->group) {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }
            if ($item['field']->group && $item['field']->type != 'panel') {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }

            $item['group'] = $group;
            $item['groupLevel'] = $groupLevel;

            if ($item['field']->group) {
                $item['fields'] = [];
            }

            if ($item['field']->group && $item['field']->type != 'panel') {
                $groupLevel = $groupLevel.'.0';
            }

            if ($item['field']->type == 'panel') {
                $groupLevel = $groupLevel.'.0';
            }

            if ($item['field']->type == 'tab') {
                $groupLevel = $groupLevel.'.0';
            }

            return $item;
        });

        $nestedArray = $fields->reduce(function ($nestedArray, $item) {
            $levels = explode('.', $item['groupLevel']);

            $currentLevel = &$nestedArray;

            foreach ($levels as $level) {
                if (! isset($currentLevel[$level])) {
                    $currentLevel[$level] = $item;
                }

                $currentLevel = &$currentLevel[$level]['fields'];
            }

            return $nestedArray;
        }, []);

        $nestedArray = collect($nestedArray)->map(function ($item, $key) {
            $item['fields'] = collect($item['fields'])->values();

            return $item;
        })->values();

        return $nestedArray;

        return  $next($fields);
    }
}
