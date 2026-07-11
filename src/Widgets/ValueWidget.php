<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ValueWidget extends Widget
{
    public $end;

    #[Locked]
    public $method = 'count';

    public $start;

    #[Locked]
    public $widget;

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];

        // Never let an unknown/tampered column reach the raw-SQL identifier path.
        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        $isMetaColumn = $column && $this->model->isMetaField($column);

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        // Apply the query scope only if it maps to a real Eloquent scope on the model.
        $queryScope = optional($this->widget)['queryScope'];
        if ($queryScope && method_exists($this->model, 'scope'.ucfirst($queryScope))) {
            $posts->{$queryScope}();
        }

        if ($isMetaColumn) {
            $posts->select($this->model->getTable().'.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on($this->model->getQualifiedKeyName(), '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', $this->model->getMorphClass());
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            default => $posts->count(),
        };
    }

    public function getValuesProperty()
    {
        $currentStart = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $currentEnd = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        // Calculate the duration between start and end dates
        $duration = $currentStart->diffInDays($currentEnd);

        // Calculate previousStart and previousEnd based on the duration
        $previousStart = $currentStart->copy()->subDays($duration);
        $previousEnd = $currentStart;

        return cache()->remember($this->getCacheKeyProperty(), $this->getCacheDurationProperty(), function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $this->format($current),
                'previous' => $this->format($previous),
                'change' => $this->format($change),
            ];
        });
    }

    public function mount()
    {
        if ($this->widget['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.value');
    }

    #[On('dateFilterUpdated')]
    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
