<?php

namespace Aura\Base\Tests\Support;

use Aura\Base\Tests\Fixtures\ComponentSlots\HostMediaManager;
use Aura\Base\Tests\TestCase;

class LegacyMediaManagerOverrideTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('aura.components.media-manager', HostMediaManager::class);
    }
}
