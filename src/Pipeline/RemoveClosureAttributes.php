<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class RemoveClosureAttributes implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->map(function ($field) {
            if (isset($field['validation']) && $field['validation'] instanceof Closure) {
                unset($field['validation']);
            }

            if (isset($field['relation']) && $field['relation'] instanceof Closure) {
                unset($field['relation']);
            }

            return $field;
        });

        return $next($fields);
    }
}
