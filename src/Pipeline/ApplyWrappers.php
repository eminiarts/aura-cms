<?php

namespace Aura\Base\Pipeline;

use Closure;
use Illuminate\Support\Str;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $addedGlobalWrappers = [];
        $addedNonGlobalWrappers = [];

        foreach ($fields as $field) {
            // If no wrapper, add the field as is
            if (!$field['field']->wrapper) {
                $newFields[] = $field;
                continue;
            }

            $isGlobal = isset($field['global']) && $field['global'] === true;
            $relevantWrappers = $isGlobal ? $addedGlobalWrappers : $addedNonGlobalWrappers;

            // Add wrapper if:
            // 1. We haven't added this type of wrapper (global/non-global) before
            // 2. OR if wrap is explicitly set to true
            if (!in_array($field['field']->wrapper, $relevantWrappers) || optional($field)['wrap'] === true) {
                // Add the wrapper field
                $wrapperField = [
                    'label' => $field['field']->wrapper,
                    'name'  => $field['field']->wrapper,
                    'type'  => $field['field']->wrapper,
                    'slug'  => Str::slug($field['field']->wrapper),
                    'field' => app($field['field']->wrapper),
                ];

                // Set global flag on wrapper if field is global
                if ($isGlobal) {
                    $wrapperField['global'] = true;
                    $addedGlobalWrappers[] = $field['field']->wrapper;
                } else {
                    $addedNonGlobalWrappers[] = $field['field']->wrapper;
                }

                $newFields[] = $wrapperField;
            }

            // Add the field as is
            $newFields[] = $field;
        }

        // Return a collection if the input was a collection
        if ($fields instanceof \Illuminate\Support\Collection) {
            $newFields = collect($newFields);
        }

        // For debugging
        ray($newFields, $addedGlobalWrappers, $addedNonGlobalWrappers);

        // Pass the new fields to the next pipe
        return $next($newFields);
    }
}   
