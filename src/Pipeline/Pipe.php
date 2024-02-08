<?php

namespace Aura\Base\Pipeline;

use Closure;

interface Pipe
{
    public function handle($content, Closure $next);
}
