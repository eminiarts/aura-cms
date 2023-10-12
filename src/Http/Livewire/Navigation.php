<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;

class Navigation extends Component
{
    public $toggledGroups = [];

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->emit('NavigationMounted');

        if (auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
            // ray($this->toggledGroups);
        } else {
            $this->toggledGroups = [];
        }
    }

    public function render()
    {
        return view('aura::livewire.navigation');
    }

    public function toggleGroup($group)
    {
        if (in_array($group, $this->toggledGroups)) {
            $this->toggledGroups = array_diff($this->toggledGroups, [$group]);
        } else {
            $this->toggledGroups[] = $group;
        }

        auth()->user()->updateOption('sidebar', $this->toggledGroups);
    }
}
