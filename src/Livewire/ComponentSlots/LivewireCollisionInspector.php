<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;

interface LivewireCollisionInspector
{
    public function assertCompatible(): void;

    public function assertReservable(string $identifier, string $intrinsicComponent, Closure $auraResolver): void;

    /**
     * @param  list<string>  $identifiers
     */
    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void;
}
