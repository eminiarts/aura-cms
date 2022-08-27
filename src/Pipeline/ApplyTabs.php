<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyTabs implements Pipe
{
    public function handle($content, Closure $next)
    {
        // Here you perform the task and return the updated $content
        // to the next pipe
        return  $next($content);
    }
}
