<?php

namespace Eminiarts\Aura\Widgets;

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

    public function getValuesProperty()
    {
        $currentStart = $this->getCarbonDate($this->start)->addDay();
        $currentEnd = $this->getCarbonDate($this->end);
        $diff = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($diff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        return [
            'current' => $this->getValue($currentStart, $currentEnd)->toArray(),
            'previous' => $this->getValue($previousStart, $previousEnd)->toArray(),
        ];
    }

    public function mount()
    {
        // dd('hier', $this->start, $this->end, $this->model, $this->widget);

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

    protected function getValue($start, $end)
    {
        $postsByDate = $this->model
        ->where('created_at', '>=', $start)
        ->where('created_at', '<', $end)
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Generate a date range between $start and $end
        $dateRange = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = 0;
        }

        // Merge date range with the results from the query
        return collect($dateRange)->merge($postsByDate);
    }

private function getCarbonDate($date)
{
    return $date instanceof Carbon ? $date : Carbon::parse($date);
}
}
