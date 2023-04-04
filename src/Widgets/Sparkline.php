<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Support\Carbon;

class Sparkline extends Widget
{
    public $widget;
    public $start;
    public $end;
    public $model;

    public $method = 'area';

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function mount($widget, $model)
    {
        $this->widget = $widget;
        $this->model = $model;

        if(optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.sparkline-area');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getValuesProperty($selected = 30)
    {
        $currentStart = $this->start;
        $currentEnd = $this->end;

        // dd($currentStart, $currentEnd, Carbon::create($currentStart)->diffInDays($currentEnd));

        // get diff of start and end
        $diff = Carbon::create($currentStart)->diffInDays($currentEnd);

        $previousStart = Carbon::create($currentStart)->subDays($diff);
        $previousEnd = $currentStart;

        $current = $this->getValue($currentStart, $currentEnd);
        $previous = $this->getValue($previousStart, $previousEnd);

        // $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current' => $current->toArray(),
            'previous' => $previous->toArray(),
            // 'change' => $change,
        ];
    }

    protected function getValue($start, $end)
    {
        $dateRange = collect();
        $currentDate = Carbon::parse($start);

        while ($currentDate->lte($end)) {
            $dateRange->push($currentDate->toDateString());
            $currentDate->addDay();
        }

        $posts = $this->model->whereBetween('created_at', [$start, $end])->get();

        $dailyCounts = $dateRange->mapWithKeys(function ($date) use ($posts) {
            $count = $posts->filter(function ($post) use ($date) {
                return Carbon::parse($post->created_at)->toDateString() === $date;
            })->count();

            return [$date => rand(10, 100)];
        });

        return match ($this->method) {
            'avg' => $dailyCounts->avg(),
            'sum' => $dailyCounts->sum(),
            'min' => $dailyCounts->min(),
            'max' => $dailyCounts->max(),
            default => $dailyCounts,
        };
    }
}
