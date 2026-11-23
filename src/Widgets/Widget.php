<?php

namespace Aura\Base\Widgets;

use Aura\Base\Resources\User;
use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Stringable;
use UnitEnum;

class Widget extends Component
{
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
     * Keep the widget config serializable, also when a widget is mounted directly.
     */
    public function dehydrate(): void
    {
        if (is_array($this->widget)) {
            $this->widget = static::livewireSafe($this->widget);
        }
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

    /**
     * Reduce a widget config to values Livewire can dehydrate.
     *
     * Resources may put closures (e.g. a `query` callback) or other objects into
     * getWidgets(); Livewire cannot snapshot those and the whole page would fail.
     * Closures and unknown objects are dropped, enums and stringables become
     * scalars, arrayables become arrays.
     */
    public static function livewireSafe(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $safe = [];

            foreach ($value as $key => $item) {
                if (! is_object($item) || $item instanceof Arrayable || $item instanceof UnitEnum || $item instanceof Stringable) {
                    $safe[$key] = static::livewireSafe($item);
                }
            }

            return $safe;
        }

        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof Stringable => (string) $value,
            is_object($value) => null,
            default => $value,
        };
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
}
