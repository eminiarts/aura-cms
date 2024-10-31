<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $this->processFields($fields);
        return $next($fields);
    }

    private function processFields($fields)
    {
        $newFields = [];
        $i = 0;
        $fieldsCount = count($fields);

        while ($i < $fieldsCount) {
            $field = $fields[$i];

            // If wrap is true, start a new wrapper
            if (isset($field['wrap']) && $field['wrap'] === true) {
                $wrapperField = [
                    'label'  => 'Wrapper',
                    'name'   => 'Wrapper',
                    'type'   => 'Wrapper',
                ];

                // Collect consecutive fields with 'wrap' => true
                while (
                    $i < $fieldsCount &&
                    isset($fields[$i]['wrap']) &&
                    $fields[$i]['wrap'] === true
                ) {
                    $wrapperField['fields'][] = $fields[$i];
                    $i++;
                }

                $newFields[] = $wrapperField;
            } else {
                // Process nested fields recursively if they exist
                if (isset($field['fields']) && is_array($field['fields'])) {
                    $field['fields'] = $this->processFields($field['fields']);
                }
                $newFields[] = $field;
                $i++;
            }
        }

        return $newFields;
    }
}
