<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class SlideOver extends Component
{
    #[On('slideOverOpened')]
    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }

    public function mount($id = null, $params = null)
    {
        if ($id) {
        }
    }

    public function render()
    {
        return view('aura::livewire.slide-over');
    }
}
