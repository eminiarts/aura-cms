<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class FilterEditFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->filter(function ($field) {
            if (optional($field)['on_forms'] === false) {
                return false;
            }

            if (optional($field)['on_edit'] === false) {
                return false;
            }

            return true;
        })->values();


        return  $next($fields);
    }
}
