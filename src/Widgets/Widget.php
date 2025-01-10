<?php

namespace Aura\Base\Widgets;

use Livewire\Component;

class Widget extends Component
{
    /**
     * Whether the widget is cached.
     *
     * @var bool
     */
    public $isCached = false;

    /**
     * Whether the widget is loaded.
     *
     * @var bool
     */
    public $loaded = false;

    /**
     * The start date/time for the widget data.
     *
     * @var string|null
     */
    public $start;

    /**
     * The end date/time for the widget data.
     *
     * @var string|null
     */
    public $end;

    /**
     * The widget configuration.
     *
     * @var array
     */
    public $widget;

    /**
     * The cache key for the widget.
     *
     * @var string|null
     */
    protected $cacheKey;

    public function format($value)
    {
        $formatted = number_format($value, 2, '.', "'");

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
