<?php

namespace Eminiarts\Aura\Widgets;

use Livewire\Component;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Eminiarts\Aura\Resources\Post;

class Widgets extends Component
{
    public $widgets;
    public $model;
    public $start;
    public $end;
    public $selected;

    public function mount($widgets, $model)
    {
        $this->widgets = $widgets;
        $this->model= $model;

        $this->selected = $this->model->widgetSettings['default'] ?? 'all';
        $this->updatedSelected();
    }

    public function updatedSelected()
    {
        if ($this->selected === 'custom') {
            $this->start = Carbon::now()->subDays(30)->toDateString();
            $this->end = Carbon::now()->toDateString();
        } else {
            $this->updateDates();
        }

        $this->emit('dateFilterUpdated', $this->start, $this->end);
    }

    protected function updateDates()
    {
        if ($this->selected === 'all') {
            $this->start = null;
            $this->end = null;
        } elseif ($this->selected === 'ytd') {
            $this->start = Carbon::now()->startOfYear()->toDateString();
            $this->end = Carbon::now()->toDateString();
        } elseif ($this->selected === 'qtd') {
            $this->start = Carbon::now()->startOfQuarter()->toDateString();
            $this->end = Carbon::now()->toDateString();
        } elseif ($this->selected === 'mtd') {
            $this->start = Carbon::now()->startOfMonth()->toDateString();
            $this->end = Carbon::now()->toDateString();
        } elseif ($this->selected === 'wtd') {
            $this->start = Carbon::now()->startOfWeek()->toDateString();
            $this->end = Carbon::now()->toDateString();
        } elseif ($this->selected === 'last-month') {
            $this->start = Carbon::now()->subMonth()->startOfMonth()->toDateString();
            $this->end = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($this->selected === 'last-week') {
            $this->start = Carbon::now()->subWeek()->startOfWeek()->toDateString();
            $this->end = Carbon::now()->subWeek()->endOfWeek()->toDateString();
        } elseif ($this->selected === 'last-quarter') {
            $this->start = Carbon::now()->subQuarter()->startOfQuarter()->toDateString();
            $this->end = Carbon::now()->subQuarter()->endOfQuarter()->toDateString();
        } elseif ($this->selected === 'last-year') {
            $this->start = Carbon::now()->subYear()->startOfYear()->toDateString();
            $this->end = Carbon::now()->subYear()->endOfYear()->toDateString();
        } else {
            $interval = intval(preg_replace('/[^0-9]/', '', $this->selected));
            $this->start = Carbon::now()->subDays($interval)->toDateString();
            $this->end = Carbon::now()->toDateString();
        }
    }


    public function render()
    {
        return view('aura::components.widgets.index');
    }
}
