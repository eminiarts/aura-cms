<?php

namespace Eminiarts\Aura\Widgets;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Widget extends Component
{
    protected static string $view;

    public bool $loadData = false;

    public function init()
    {
        $this->loadData = true;
    }

    public $settings;

    /**
     * The width of the card (1/3, 2/3, 1/2, 1/4, 3/4, or full).
     *
     * @var string
     */
    public $width;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }


    public static function canView(): bool
    {
        return true;
    }

    public function cache($callback, $name = null)
    {
        $key = ($this->uriKey() ?? $this->id) . '_' . $name . '_' . $this->range;

        return cache()->remember($key, $this->cacheFor() ?? now()->addMinutes(5), $callback);
    }



    public function render(): View
    {
        // dd(static::$view, $this->getViewData());
        return view(static::$view);
    }

    /**
     * Convert datetime to application timezone.
     *
     * @param  \Carbon\CarbonInterface  $datetime
     * @return \Carbon\CarbonInterface
     */
    protected function asQueryDatetime($datetime)
    {
        if (! $datetime instanceof \DateTimeImmutable) {
            return $datetime->copy()->timezone(config('app.timezone'));
        }

        return $datetime->timezone(config('app.timezone'));
    }

    /**
     * Format date between.
     *
     * @param  array  $ranges
     * @return array
     */
    protected function formatQueryDateBetween(array $ranges)
    {
        return array_map(function ($datetime) {
            return $this->asQueryDatetime($datetime);
        }, $ranges);
    }
}
