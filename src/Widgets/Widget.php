<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;

class Widget extends Component
{
    public $loaded = false;
    public $isCached = false;

    public function loadWidget()
    {
        sleep(5);
        $this->loaded = true;
    }

    public function mount($widget, $model)
    {
        // Check if the widget is cached
        if(cache()->has($this->cacheKey)) {
            $this->isCached = true;
            $this->loaded=true;
        }

    }

    public function getCacheKeyProperty()
    {
        return md5(auth()->user()->current_team_id . $this->widget['slug']);
    }

    public function getCacheDurationProperty()
    {
        return $this->widget['cache']['duration'] ?? 60;
    }

}
