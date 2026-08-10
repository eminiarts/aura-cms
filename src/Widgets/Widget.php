<?php

namespace Aura\Base\Widgets;

use Livewire\Attributes\Locked;
use Livewire\Component;

class Widget extends Component
{
    use InteractsWithWidgetCache;

    /**
     * The end date/time for the widget data.
     *
     * @var string|null
     */
    public $end;

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
     * The resource model the widget operates on.
     */
    #[Locked]
    public $model;

    /**
     * The start date/time for the widget data.
     *
     * @var string|null
     */
    public $start;

    /**
     * The widget configuration.
     *
     * @var array
     */
    #[Locked]
    public $widget;

    public function clearCache(): void
    {
        cache()->forget($this->getCacheKeyProperty());
        $this->isCached = false;
    }

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
        $cache = $this->widget['cache'] ?? [];

        if (is_numeric($cache)) {
            return (int) $cache;
        }

        return is_array($cache) ? ($cache['duration'] ?? 60) : 60;
    }

    public function getCacheKeyProperty()
    {
        return 'aura.widget.v1.'.$this->widgetCacheFingerprint();
    }

    public function loadWidget()
    {
        $this->loaded = true;
    }

    /**
     * Determine whether a column is a real, known field of the widget's model.
     *
     * The widget config (column/method/queryScope) is developer-defined and the
     * config properties are #[Locked], but this additionally guards the raw-SQL
     * identifier paths in the concrete widgets so a tampered or unknown column can
     * never reach selectRaw()/DB::raw() as an interpolated identifier.
     */
    protected function isSafeColumn($column): bool
    {
        if (! is_string($column) || $column === '') {
            return false;
        }

        return in_array($column, $this->model->getBaseFillable(), true)
            || in_array($column, $this->model->inputFieldsSlugs(), true);
    }

    /**
     * Declare only the actor/resource dimensions that can change this widget's result.
     *
     * @return list<'resource'|'team'|'user'>
     */
    protected function widgetCacheContextDimensions(): array
    {
        return [];
    }
}
