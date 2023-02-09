<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;

class SlideOver extends Component
{
    protected $listeners = ['slideOverOpened' => 'activate'];

    public function activate($id, $params)
    {
        $this->mount($id, $params);
        // dd('activated', $id, $params);
    }

    public function mount($id = null, $params = null)
    {
        if ($id) {
            // dd('mounted', $id, $params);
        }
    }

    public function render()
    {
        return view('aura::livewire.slide-over');
    }
}
