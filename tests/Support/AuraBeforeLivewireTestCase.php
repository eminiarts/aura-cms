<?php

namespace Aura\Base\Tests\Support;

use Aura\Base\Tests\TestCase;
use Livewire\LivewireServiceProvider;

class AuraBeforeLivewireTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_values(array_filter(
            parent::getPackageProviders($app),
            fn (string $provider): bool => $provider !== LivewireServiceProvider::class,
        ));
    }
}
