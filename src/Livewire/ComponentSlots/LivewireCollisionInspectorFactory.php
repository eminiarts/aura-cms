<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Composer\InstalledVersions;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;
use Throwable;

class LivewireCollisionInspectorFactory
{
    public function __construct(
        private readonly Finder $finder,
        private readonly Factory $factory,
    ) {}

    public function forVersion(string $version): LivewireCollisionInspector
    {
        $normalized = ltrim($version, 'v');

        if (version_compare($normalized, '4.3.5', '>=') && version_compare($normalized, '4.4.0', '<')) {
            return new Livewire43CollisionInspector($this->finder, $this->factory);
        }

        throw new UnsupportedLivewireInternals(
            "Unsupported Livewire internals version [{$version}]; CORE-20 supports livewire/livewire ~4.3.5."
        );
    }

    public function make(): LivewireCollisionInspector
    {
        try {
            $version = InstalledVersions::getPrettyVersion('livewire/livewire')
                ?? InstalledVersions::getVersion('livewire/livewire');
        } catch (Throwable) {
            $version = null;
        }

        if (! is_string($version) || $version === '') {
            throw new UnsupportedLivewireInternals('Unable to determine the installed livewire/livewire version.');
        }

        return $this->forVersion($version);
    }
}
