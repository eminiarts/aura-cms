<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;

class ValueWidget extends Widget
{
    public $widget;
    public $start;
    public $end;
    public $model;

    public $method = 'count';

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function mount($widget, $model)
    {
        $this->widget = $widget;
        $this->model = $model;

        if($this->widget['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.total-posts');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getValuesProperty($selected = 30)
    {
        $currentStart = now()->subDays($selected);
        $currentEnd = now();
        $previousStart = $currentStart->copy()->subDays($selected);
        $previousEnd = $currentStart;

        $current = $this->getValue($currentStart, $currentEnd);
        $previous = $this->getValue($previousStart, $previousEnd);

        $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
        ];
    }

    protected function getValue($start, $end)
    {
        $posts = $this->model->whereBetween('created_at', [$start, $end]);

        return match ($this->method) {
            'avg' => $posts->avg('value_column'),
            'sum' => $posts->sum('value_column'),
            'min' => $posts->min('value_column'),
            'max' => $posts->max('value_column'),
            default => $posts->count(),
        };
    }
}
