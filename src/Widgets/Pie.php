<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class Pie extends Widget
{
    public $end;

    #[Locked]
    public $method = 'count';

    #[Locked]
    public $model;

    public $start;

    #[Locked]
    public $widget;

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];
        $table = $this->model->getTable();

        // Never let an unknown/tampered column reach the raw-SQL identifier path.
        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        $posts = $this->model->query()
            ->where($table.'.created_at', '>=', $start)
            ->where($table.'.created_at', '<', $end);

        if (! $column) {
            return ['Total' => $posts->count()];
        }

        if ($column && $this->model->isMetaField($column)) {
            $posts->leftJoin('meta', function ($join) use ($column) {
                $join->on($this->model->getQualifiedKeyName(), '=', 'meta.metable_id')
                    ->where('meta.key', '=', $column)
                    ->where('meta.metable_type', '=', $this->model->getMorphClass());
            });

            $aggregateExpression = 'CAST(meta.value as SIGNED)';
            $labelExpression = 'meta.value';
        } else {
            $aggregateExpression = $table.'.'.$column;
            $labelExpression = $table.'.'.$column;
        }

        $method = in_array($this->method, ['avg', 'sum', 'min', 'max'], true) ? strtoupper($this->method) : 'COUNT';
        $aggregateSelect = $method === 'COUNT' ? 'COUNT(*)' : $method.'('.$aggregateExpression.')';

        return $posts->selectRaw($labelExpression.' as label, '.$aggregateSelect.' as aggregate')
            ->groupBy(DB::raw($labelExpression))
            ->pluck('aggregate', 'label')
            ->mapWithKeys(fn ($value, $label) => [(string) ($label ?: 'Empty') => $value])
            ->toArray();
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

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            // $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $current,
                'previous' => $previous,
                // 'change' => $change,
            ];
        });
    }

    public function mount()
    {
        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.pie');
    }

    #[On('dateFilterUpdated')]
    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
