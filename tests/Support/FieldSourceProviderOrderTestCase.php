<?php

namespace Aura\Base\Tests\Support;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Tests\Fixtures\Plugin\FieldPluginServiceProvider;
use Aura\Base\Tests\TestCase;
use LogicException;

abstract class FieldSourceProviderOrderTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        $providers = parent::getPackageProviders($app);
        $auraIndex = array_search(AuraServiceProvider::class, $providers, true);

        if ($auraIndex === false) {
            throw new LogicException('Aura service provider is missing from the test application.');
        }

        $pluginIndex = $this->pluginLoadsBeforeAura() ? $auraIndex : $auraIndex + 1;
        array_splice($providers, $pluginIndex, 0, [FieldPluginServiceProvider::class]);

        return $providers;
    }

    abstract protected function pluginLoadsBeforeAura(): bool;
}
