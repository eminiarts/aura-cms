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

            $wrapperInfo = $this->getFieldWrapper($field);

            

            if ($wrapperInfo && !in_array($wrapperInfo['type'], $addedWrappers)) {
                // Add the wrapper field once
                $wrapperField = [
                    'label' => $wrapperInfo['type'],
                    'name'  => $wrapperInfo['type'],
                    'type'  => $wrapperInfo['type'],
                    'slug'  => Str::slug($wrapperInfo['type']),
                    'field' => app($wrapperInfo['type']),
                ];

                if ($wrapperInfo['class']) {
                    $wrapperField['field'] = new $wrapperInfo['class']();
                }

                $newFields[] = $wrapperField;
                $addedWrappers[] = $wrapperInfo['type'];
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

    private function getFieldWrapper($field)
    {
        return [
            'type'  => $field['field']->wrapper,
            'class' => $field['field']->wrapperFieldClass ?? null,
            'slug'  => $field['field']->wrapperSlug ?? Str::slug($field['field']->wrapper),
        ];
    }
}   
