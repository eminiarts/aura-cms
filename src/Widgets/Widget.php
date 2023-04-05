<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;

class Widget extends Component
{
    public $isCached = false;

    public $loaded = false;

    public function format($value)
    {
        $formatted = number_format($value, 2);

        if (substr($formatted, -3) === '.00') {
            $formatted = substr($formatted, 0, -3);
        }

        return $formatted;
    }

    public function getCacheDurationProperty()
    {
        return $this->widget['cache']['duration'] ?? 60;
    }

    public function getCacheKeyProperty()
    {
        return md5(auth()->user()->current_team_id.$this->widget['slug'].$this->start.$this->end);
    }

    public function loadWidget()
    {
        $this->loaded = true;
    }

    public function mount()
    {
        // Check if the widget is cached
        if (cache()->has($this->cacheKey)) {
            $this->isCached = true;
            $this->loaded = true;
        }
    }
}
