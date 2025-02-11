<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Sparkline extends Widget
{
    public $end;

    public $method = 'area';

    public $model;

    public $start;

    public $widget;

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function getCarbonDate($date)
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];
        $method = $this->method;

        $query = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'));

        if ($column && $this->model->isMetaField($column)) {
            $query->leftJoin('meta', function ($join) use ($column) {
                $join->on('posts.id', '=', 'meta.metable_id')
                    ->where('meta.key', '=', $column)
                    ->where('meta.metable_type', '=', get_class($this->model));
            });

            if (in_array($method, ['avg', 'sum', 'min', 'max'])) {
                $query->addSelect(DB::raw("{$method}(CAST(meta.value as SIGNED)) as count"));
            } else {
                $query->addSelect(DB::raw('COUNT(*) as count'));
            }
        } else {
            $query->addSelect(DB::raw('COUNT(*) as count'));
        }

        $postsByDate = $query->get()->pluck('count', 'date')->toArray();

        // Generate a date range between $start and $end
        $dateRange = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = 0;
        }

        // Merge date range with the results from the query
        return collect($dateRange)->merge($postsByDate);
    }

    public function getValuesProperty()
    {
        $currentStart = $this->getCarbonDate($this->start)->addDay();
        $currentEnd = $this->getCarbonDate($this->end);
        $diff = round($currentStart->diffInDays($currentEnd));

        $previousStart = $currentStart->copy()->subDays($diff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        return [
            'current' => $this->getValue($currentStart, $currentEnd)->toArray(),
            'previous' => $this->getValue($previousStart, $previousEnd)->toArray(),
        ];
    }

    public function mount()
    {
        if (optional($this->widget)['method']) {
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
}
