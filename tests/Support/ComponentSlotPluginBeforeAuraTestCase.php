<?php

namespace Aura\Base\Tests\Support;

class ComponentSlotPluginBeforeAuraTestCase extends ComponentSlotProviderOrderTestCase
{
    protected function pluginLoadsBeforeAura(): bool
    {
        return true;
    }
}
