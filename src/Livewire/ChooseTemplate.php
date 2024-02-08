<?php

namespace Aura\Base\Livewire;

use LivewireUI\Modal\ModalComponent;

class ChooseTemplate extends ModalComponent
{
    public function render()
    {
        return view('aura::livewire.choose-template');
    }
}
