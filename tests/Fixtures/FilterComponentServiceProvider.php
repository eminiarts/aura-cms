<?php

namespace Aura\Base\Tests\Fixtures;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilterComponentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/filter-components', 'test-filters');
    }
}
