<?php

namespace Aura\Base\Tests\Fixtures\ComponentSlots;

use Aura\Base\Facades\Aura;
use Illuminate\Support\ServiceProvider;

class ComponentSlotPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Aura::registerComponentSlots('fixture/component-slots', [
            'global-search' => PluginGlobalSearch::class,
        ]);
    }
}
