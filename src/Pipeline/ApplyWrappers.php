<?php

namespace Aura\Base\Pipeline;

use Closure;
use Illuminate\Support\Str;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $addedWrappers = [];

        foreach ($fields as $field) {
            // If no wrapper, add the field as is
            if (!$field['field']->wrapper) {
                $newFields[] = $field;
                continue;
            }

            if (!in_array($field['field']->wrapper, $addedWrappers)) {
                // Add the wrapper field once
                $wrapperField = [
                    'label' => $field['field']->wrapper,
                    'name'  => $field['field']->wrapper,
                    'type'  => $field['field']->wrapper,
                    'slug'  => $field['field']->wrapperSlug ?? Str::slug($field['field']->wrapper),
                    'field' => app($field['field']->wrapper),
                ];

                $newFields[] = $wrapperField;
                $addedWrappers[] = $field['field']->wrapper;
            }

            // Add the field as is
            $newFields[] = $field;
        }

        // Return a collection if the input was a collection
        if ($fields instanceof \Illuminate\Support\Collection) {
            $newFields = collect($newFields);
        }

        // For debugging
        ray($newFields);

        // Pass the new fields to the next pipe
        return $next($newFields);
    }
}   
