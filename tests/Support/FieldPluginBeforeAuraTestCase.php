<?php

namespace Aura\Base\Tests\Support;

class FieldPluginBeforeAuraTestCase extends FieldSourceProviderOrderTestCase
{
    protected function pluginLoadsBeforeAura(): bool
    {
        return true;
    }
}
