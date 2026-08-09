<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use Livewire\Component;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;

class DefaultLivewireComponentSlotBridge implements LivewireComponentSlotBridge
{
    public function __construct(
        private readonly LivewireCollisionInspector $inspector,
        private readonly Finder $finder,
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
        $this->factory->resolveMissingComponent($resolver);
    }

    /**
     * @param  class-string<Component>  $component
     */
    public function register(string $name, string $component): void
    {
        $this->finder->addComponent(name: $name, class: $component);
    }

    public function reserve(string $name, string $intrinsicComponent, Closure $auraResolver): void
    {
        $this->inspector->assertReservable($name, $intrinsicComponent, $auraResolver);
        $this->finder->addComponent(name: $name, class: ComponentSlotAliasReservation::class);
    }

    public function resolve(string $name): array
    {
        return $this->factory->resolveComponentNameAndClass($name);
    }
}
