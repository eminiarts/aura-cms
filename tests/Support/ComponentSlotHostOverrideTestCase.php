<?php

namespace Aura\Base\Tests\Support;

use Aura\Base\Tests\Fixtures\ComponentSlots\HostGlobalSearch;
use Aura\Base\Tests\TestCase;

class ComponentSlotHostOverrideTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('aura.component-slots.global-search', HostGlobalSearch::class);
    }
}
