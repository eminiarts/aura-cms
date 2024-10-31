<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $index = 0;
        $fieldsCount = count($fields);

        while ($index < $fieldsCount) {
            $field = $fields[$index];

            if (isset($field['wrapper']) && $field['wrapper']) {
                $wrapperType = $field['wrapper'];
                $fieldType = $field['type'];

                // Collect all consecutive fields of the same type and wrapper
                $wrappedFields = [];
                while (
                    $index < $fieldsCount &&
                    $fields[$index]['type'] === $fieldType &&
                    isset($fields[$index]['wrapper']) &&
                    $fields[$index]['wrapper'] === $wrapperType
                ) {
                    $wrappedFields[] = $fields[$index];
                    $index++;
                }

                // Create the wrapper field
                $wrapperField = [
                    'label'  => $wrapperType,
                    'name'   => $wrapperType,
                    'type'   => $wrapperType,
                    'fields' => $wrappedFields,
                    'style'  => [],
                ];

                $newFields[] = $wrapperField;
            } else {
                $newFields[] = $field;
                $index++;
            }
        }

        dd($newFields, $fields);

        // Pass the new fields to the next pipe
        return $next($newFields);
    }
}
