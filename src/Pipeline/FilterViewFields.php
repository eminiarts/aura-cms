<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class FilterViewFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->filter(function ($field) {
            // if there is a on_view = false, filter it out
            if (optional($field)['on_view'] === false) {
                return false;
            }

            return true;
        });

        return $next($fields);
    }
}
