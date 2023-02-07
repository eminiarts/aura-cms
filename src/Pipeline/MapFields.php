<?php

namespace App\Aura\Pipeline;

use Closure;

class MapFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        return $next($fields->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        }));
    }
}
