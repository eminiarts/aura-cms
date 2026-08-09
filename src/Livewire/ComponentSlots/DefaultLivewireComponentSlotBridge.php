<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use Livewire\Component;
use Livewire\Factory\Factory;
use Livewire\LivewireManager;

class DefaultLivewireComponentSlotBridge implements LivewireComponentSlotBridge
{
    public function __construct(
        private readonly LivewireCollisionInspector $inspector,
        private readonly LivewireManager $livewire,
        private readonly Factory $factory,
    ) {}

    public function assertCompatible(): void
    {
        $this->inspector->assertCompatible();
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        $this->inspector->assertUnclaimed($identifiers, $auraResolver);
    }

    public function installMissingResolver(Closure $resolver): void
    {
        $this->livewire->resolveMissingComponent($resolver);
    }

    /**
     * @param  class-string<Component>  $component
     */
    public function register(string $name, string $component): void
    {
        $this->livewire->component($name, $component);
    }

    public function resolve(string $name): array
    {
        return $this->factory->resolveComponentNameAndClass($name);
    }
}
