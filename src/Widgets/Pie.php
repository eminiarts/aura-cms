<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Support\Facades\DB;

class Pie extends Widget
{
    public $widget;
    public $start;
    public $end;
    public $model;

    public $method = 'count';

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function mount()
    {
        if(optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.pie');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
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

    public function getValue($start, $end)
    {

        return [
            'tag-1' => rand(10, 50),
            'tag-2' => rand(10, 50),
            'tag-3' => rand(10, 50),
            'tag-4' => rand(10, 50),
        ];

        $column = optional($this->widget)['column'];

        $posts = $this->model->query()
        ->where('created_at', '>=', $start)
        ->where('created_at', '<', $end);

        if($column && $this->model->isMetaField($column)) {
            $posts->select('posts.*', DB::raw("CAST(post_meta.value as SIGNED) as $column"))
            ->leftJoin('post_meta', function ($join) use ($column) {
                $join->on('posts.id', '=', 'post_meta.post_id')
            ->where('post_meta.key', '=', $column);
            });
        }

        return match ($this->method) {
            'avg' => $posts->avg($this->model->isMetaField($column) ? DB::raw("CAST(post_meta.value as SIGNED)") : $column),
            'sum' => $posts->sum($this->model->isMetaField($column) ? DB::raw("CAST(post_meta.value as SIGNED)") : $column),
            'min' => $posts->min($this->model->isMetaField($column) ? DB::raw("CAST(post_meta.value as SIGNED)") : $column),
            'max' => $posts->max($this->model->isMetaField($column) ? DB::raw("CAST(post_meta.value as SIGNED)") : $column),
            default => $posts->count(),
        };
    }
}