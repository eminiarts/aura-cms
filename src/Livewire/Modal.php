<?php

namespace Eminiarts\Aura\Livewire;

use Livewire\Component;

class Modal extends Component
{
    protected $listeners = ['modalOpened' => 'activate'];

    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }

    public function render()
    {
        return view('aura::livewire.modal');
    }
}
