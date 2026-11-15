<?php

namespace Aura\Base\Widgets;

use Aura\Base\Resources\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Widget extends Component
{
    /**
     * Render as a cell of the slim widget strip on index pages (see components/widgets/index).
     *
     * @var bool
     */
    public $compact = false;

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
    public $widget;

    /**
     * The comparison the change refers to, "vs previous 30 days": the range before the selected one,
     * of the same length. Null for an open range (All).
     */
    public function comparisonLabel(): ?string
    {
        $days = $this->periodDays();

        if ($days === null) {
            return null;
        }

        return $days === 1 ? __('vs previous day') : __('vs previous :count days', ['count' => $days]);
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
        return $this->widget['cache']['duration'] ?? 60;
    }

    public function getCacheKeyProperty()
    {
        /** @var User $user */
        $user = Auth::user();
        $teamId = $user->current_team_id ?? 0;

        // Scope by model type as well — different resources may reuse the same widget slug.
        $modelType = $this->model ? $this->model->getType() : '';

        return md5($teamId.$modelType.$this->widget['slug'].$this->start.$this->end);
    }

    public function loadWidget()
    {
        $this->loaded = true;
    }

    public function mount()
    {
        // Check if the widget is cached
        if (cache()->has($this->getCacheKeyProperty())) {
            $this->isCached = true;
            $this->loaded = true;
        }
    }

    /**
     * The selected range in words, "last 30 days", for a widget name with a :period placeholder
     * ("New in :period"). Every range counts records created in it.
     */
    public function periodLabel(): string
    {
        $days = $this->periodDays();

        if ($days === null) {
            return __('all time');
        }

        return $days === 1 ? __('last day') : __('last :count days', ['count' => $days]);
    }

    /**
     * Whether the widget has a compact cell for the index strip. Large charts (bar, donut, pie)
     * keep their card below the strip.
     */
    public static function supportsCompact(): bool
    {
        return false;
    }

    /**
     * The widget's name, its :period placeholder filled with the selected range.
     */
    public function title(): string
    {
        return __($this->widget['name'] ?? '', ['period' => $this->periodLabel()]);
    }

    protected function isSafeColumn($column): bool
    {
        if (! is_string($column) || $column === '') {
            return false;
        }

        return in_array($column, $this->model->getBaseFillable(), true)
            || in_array($column, $this->model->inputFieldsSlugs(), true);
    }

    /**
     * Determine whether a column is a real, known field of the widget's model.
     *
     * The widget config (column/method/queryScope) is developer-defined and the
     * config properties are #[Locked], but this additionally guards the raw-SQL
     * identifier paths in the concrete widgets so a tampered or unknown column can
     * never reach selectRaw()/DB::raw() as an interpolated identifier.
     */
    /**
     * Length of the selected range in whole days, null when the range is open.
     */
    protected function periodDays(): ?int
    {
        if (blank($this->start) || blank($this->end)) {
            return null;
        }

        $days = (int) round(Carbon::parse($this->start)->diffInDays(Carbon::parse($this->end)));

        return max($days, 1);
    }
}
