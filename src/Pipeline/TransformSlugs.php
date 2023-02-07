<?php

namespace Eminiarts\Aura\Aura\Pipeline;

use Closure;

class TransformSlugs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->map(function ($item, $key) use ($fields) {
            // get the parent
            $parent = $fields->where('_id', $item['_parent_id'])->first();

            // if the parent is a group, prepend the parent slug to the item slug
            if (optional(optional($parent)['field'])->type == 'group') {
                $item['slug'] = $parent['slug'].'.'.$item['slug'];
            }

            // if the parent is a group, prepend the parent slug to the item slug
            // if (optional(optional($parent)['field'])->type == 'repeater') {
            //     $item['slug'] = $parent['slug'] . '.*.' . $item['slug'];
            // }

            return $item;
        });

        return  $next($fields);
    }
}
