<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyParentDisplayAttributes implements Pipe
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
            $field['on_view'] = $fields->whereIn('_id', $parentIds)->pluck('on_view')->flatten(1)->filter(fn ($i) => ! is_null($i))->merge(optional($field)['on_view'])->first();

            $field['on_forms'] = $fields->whereIn('_id', $parentIds)->pluck('on_forms')->flatten(1)->filter(fn ($i) => ! is_null($i))->merge(optional($field)['on_forms'])->first();

            $field['on_edit'] = $fields->whereIn('_id', $parentIds)->pluck('on_edit')->flatten(1)->filter(fn ($i) => ! is_null($i))->merge(optional($field)['on_edit'])->first();

            $field['on_create'] = $fields->whereIn('_id', $parentIds)->pluck('on_create')->flatten(1)->filter(fn ($i) => ! is_null($i))->merge(optional($field)['on_create'])->first();

            $field['on_index'] = $fields->whereIn('_id', $parentIds)->pluck('on_index')->flatten(1)->filter(fn ($i) => ! is_null($i))->merge(optional($field)['on_index'])->first();

            return $field;
        });

        return $next($fields);
    }
}
