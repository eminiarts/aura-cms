<?php

namespace Aura\Base\Tests\Fixtures\Plugin;

use Aura\Base\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FieldPluginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('aura-field-plugin-fixture');
    }

    public function registeringPackage(): void
    {
        Aura::registerFieldSource(
            key: 'field-plugin-fixture',
            namespace: 'Aura\\Base\\Tests\\Fixtures\\Plugin\\Fields',
            path: __DIR__.'/Fields',
        );
    }
}
