<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class DoNotDeferConditionalLogic implements Pipe
{
    public function handle($fields, Closure $next)
    {
        // Get all conditional logic and pluck fields
        $conditionalLogicSlugs = $fields->pluck('conditional_logic')->flatten(1)->pluck('field')->toArray();


        // We need to set the defer property to false for all fields that are used in conditional logic
        $fields = $fields->map(function ($field) use ($conditionalLogicSlugs) {

            if(in_array($field['slug'], $conditionalLogicSlugs)) {
                $field['defer'] = false;
            }

            return $field;
        });

        return $next($fields);
    }
}
