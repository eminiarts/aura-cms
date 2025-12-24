<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public $id;

    public $params;

    #[On('modalOpened')]
    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }

    /**
     * Mount the component.
     *
     * @param  string|null  $id  The modal ID
     * @param  array|null  $params  Additional parameters
     */
    public function mount($id = null, $params = [])
    {
        $this->id = $id;
        $this->params = $params;
    }

    public function render()
    {
        return view('aura::livewire.modal');
    }
}
