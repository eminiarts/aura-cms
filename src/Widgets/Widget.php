<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;

class Widget extends Component
{
    public $loaded = false;

    public function loadWidget()
    {
        $this->loaded = true;
    }
}
