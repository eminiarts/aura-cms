<?php

namespace Aura\Base\Tests\Support;

class FieldPluginAfterAuraTestCase extends FieldSourceProviderOrderTestCase
{
    protected function pluginLoadsBeforeAura(): bool
    {
        return false;
    }
}
