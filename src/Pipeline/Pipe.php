<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

interface Pipe
{
    public function handle($content, Closure $next);
}
