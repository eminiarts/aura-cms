<?php

namespace Aura\Base\Widgets;

use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateEngine;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateRange;
use Illuminate\Support\Carbon;
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
        $column = $this->widget['column'] ?? null;

        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        $operation = $this->aggregateOperation();

        return app(AggregateEngine::class)->run(new AggregateDefinition(
            resource: $this->model::class,
            operation: $operation,
            metric: $operation === AggregateOperation::Count ? null : $column,
            range: new DateRange($start, $end),
            queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
        ))->value;
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
        if (optional($this->widget)['method']) {
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
        $this->refreshWidgetCacheState();
    }

    protected function aggregateOperation(): AggregateOperation
    {
        return match ($this->method) {
            'avg' => AggregateOperation::Average,
            'sum' => AggregateOperation::Sum,
            'min' => AggregateOperation::Minimum,
            'max' => AggregateOperation::Maximum,
            default => AggregateOperation::Count,
        };
    }

    protected function widgetCacheContextDimensions(): array
    {
        return ['resource', 'team', 'user'];
    }
}
