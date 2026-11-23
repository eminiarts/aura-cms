<?php

namespace Aura\Base\Livewire;

use Aura\Base\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

/**
 * Renders a real dashboard widget view with static values for the style guide.
 */
class StyleguideWidget extends Widget
{
    public const VIEWS = ['value', 'sparkline-area', 'sparkline-bar', 'bar', 'donut', 'pie'];

    /** @var array<string, mixed> */
    #[Locked]
    public array $demoValues = [];

    #[Locked]
    public string $widgetView = 'value';

    /**
     * Widget views read their data from $this->values.
     *
     * @return array<string, mixed>
     */
    public function getValuesProperty(): array
    {
        return $this->demoValues;
    }

    public function mount(): void
    {
        $this->isCached = true;
        $this->loaded = true;
    }

    public function render(): View
    {
        $view = in_array($this->widgetView, self::VIEWS, true) ? $this->widgetView : 'value';

        return view('aura::components.widgets.'.$view);
    }
}
