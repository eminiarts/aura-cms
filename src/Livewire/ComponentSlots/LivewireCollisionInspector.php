<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;

interface LivewireCollisionInspector
{
    public function assertCompatible(): void;

    /**
     * @param  list<string>  $identifiers
     */
    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void;
}
