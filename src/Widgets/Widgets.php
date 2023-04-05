<?php

namespace Eminiarts\Aura\Widgets;

use Illuminate\Support\Carbon;
use Livewire\Component;

class Widgets extends Component
{
    public $end;

    public $model;

    public $selected = '30d';

    public $start;

    public $test = 'bajram';

    public $widgets;

    public function mount($widgets, $model)
    {
        $this->widgets = $widgets;
        $this->model = $model;

        $this->selected = $this->model->widgetSettings['default'] ?? 'all';
        $this->updatedSelected();
    }

    public function render()
    {
        return view('aura::components.widgets.index');
    }

    public function updatedSelected()
    {
        if ($this->selected === 'custom') {
            $this->start = Carbon::now()->subDays(30);
            $this->end = Carbon::now();
        } else {
            $this->updateDates();
        }

        $this->emit('dateFilterUpdated', $this->start, $this->end);
    }

    protected function updateDates()
    {
        $now = now()->endOfDay();

        [$this->start, $this->end] = match ($this->selected) {
            'all' => [null, null],
            'ytd' => [$now->copy()->startOfYear(), $now->copy()],
            'qtd' => [$now->copy()->startOfQuarter(), $now->copy()],
            'mtd' => [$now->copy()->startOfMonth(), $now->copy()],
            'wtd' => [$now->copy()->startOfWeek(), $now->copy()],
            'last-month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'last-week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last-quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'last-year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->subDays(intval(preg_replace('/[^0-9]/', '', $this->selected))), $now->copy()],
        };
    }
}
