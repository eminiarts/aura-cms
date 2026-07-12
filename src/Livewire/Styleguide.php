<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Styleguide extends Component
{
    public bool $toggleOff = false;

    public bool $toggleOn = true;

    public function render()
    {
        return view('aura::livewire.styleguide')->layout('aura::components.layout.app');
    }
}
