<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use Livewire\Component;

interface LivewireComponentSlotBridge
{
    public function assertCompatible(): void;

    /**
     * @param  list<string>  $identifiers
     */
    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void;

    public function installMissingResolver(Closure $resolver): void;

    /**
     * @param  class-string<Component>  $component
     */
    public function register(string $name, string $component): void;

    /**
     * @return array{0: string, 1: class-string<Component>}
     */
    public function resolve(string $name): array;
}
