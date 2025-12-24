<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class ValueWidget extends Widget
{
    public $end;

    public $method = 'count';

    public $model;

    public $start;

    public $widget;

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        // Apply the query scope if it's a string
        if (optional($this->widget)['queryScope']) {
            $posts->{$this->widget['queryScope']}();
        }

        if ($column && $this->model->isMetaField($column)) {
            $posts->select('posts.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', get_class($this->model));
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
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

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
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
