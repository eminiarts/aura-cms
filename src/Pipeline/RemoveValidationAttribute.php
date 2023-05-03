<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class RemoveValidationAttribute implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->map(function ($field) {
            if (isset($field['validation'])) {
                unset($field['validation']);
            }

            return $field;
        });

        return $next($fields);
    }
}
