<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('aura::livewire.dashboard')->layout('aura::components.layout.app');
    }
}
