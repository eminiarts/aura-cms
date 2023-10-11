<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class TransformSlugs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        // Create a map of field IDs to fields for quick lookup
        $fieldsById = $fields->keyBy('_id');

        // Map over fields to adjust slugs based on parent
        $fields = $fields->map(function ($item) use ($fieldsById) {
            // Get the parent field using the lookup map
            $parent = $fieldsById->get($item['_parent_id']);

            // If the parent is a group, prepend the parent slug to the item slug
            if (isset($parent->field) && $parent->field->type === 'group') {
                $item['slug'] = $parent->slug . '.' . $item['slug'];
            }

            return $item;
        });

        return $next($fields);
    }
}
