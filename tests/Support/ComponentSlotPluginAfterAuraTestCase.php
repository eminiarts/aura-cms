<?php

namespace Aura\Base\Tests\Support;

class ComponentSlotPluginAfterAuraTestCase extends ComponentSlotProviderOrderTestCase
{
    protected function pluginLoadsBeforeAura(): bool
    {
        return false;
    }
}
