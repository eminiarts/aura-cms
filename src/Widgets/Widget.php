<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;

abstract class Widget extends Component
{
    public $widget;

    public function widget($widget)
    {
        return $this;
    }
}
