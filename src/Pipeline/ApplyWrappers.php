<?php

namespace Aura\Base\Pipeline;

use Closure;
use Illuminate\Support\Str;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $i = 0;
        $fieldsCount = count($fields);

        while ($i < $fieldsCount) {
            $wrapperFields = [];
            $field = $fields[$i];

            // Start a new wrapper
            $wrapperFields[] = $field;
            $i++;

            // Collect fields until we find a field with 'wrap' set to true
            while ($i < $fieldsCount) {
                if (isset($fields[$i]['wrap']) && $fields[$i]['wrap'] === true) {
                    // Start a new wrapper in the next iteration
                    break;
                }
                $wrapperFields[] = $fields[$i];
                $i++;
            }

            $wrapperField = [
                'label'  => 'Wrapper',
                'name'   => 'Wrapper',
                'type'   => 'Wrapper',
                'fields' => $wrapperFields,
                'slug'   => 'wrapper',
            ];

            $newFields[] = $wrapperField;
        }

        // Return a collection if the input was a collection
        if ($fields instanceof \Illuminate\Support\Collection) {
            $newFields = collect($newFields);
        }

        // Pass the new fields to the next pipe
        return $next($newFields);
    }
}