<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;

abstract class Widget extends Component
{
    protected $config;

    public $widget;


    public function widget($widget)
    {
        // $this->widget = $widget;
        // $this->withAttributes($widget);

        return $this;
    }

    abstract public function render();
}
