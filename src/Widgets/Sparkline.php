<?php

namespace Aura\Base\Widgets;

use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateEngine;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateBucket;
use Aura\Base\Reporting\DateRange;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class Sparkline extends Widget
{
    public $end;

    #[Locked]
    public $method = 'area';

    public $start;

    #[Locked]
    public $widget;

    public function getCarbonDate($date)
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    public function getValue($start, $end)
    {
        $column = $this->widget['column'] ?? null;

        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }
        $operation = $this->aggregateOperation();
        $result = app(AggregateEngine::class)->run(new AggregateDefinition(
            resource: $this->model::class,
            operation: $operation,
            metric: $operation === AggregateOperation::Count ? null : $column,
            range: new DateRange($start, $end),
            bucket: DateBucket::Day,
            timezone: is_string($this->widget['timezone'] ?? null) ? $this->widget['timezone'] : config('app.timezone', 'UTC'),
            queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
        ));
        $postsByDate = collect($result->points)->mapWithKeys(fn ($point): array => [$point->key => $point->value])->all();

        $dateRange = [];
        for ($date = Carbon::parse($start); $date->lte($end); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = 0;
        }

        return collect($dateRange)->merge($postsByDate);
    }

    public function getValuesProperty()
    {
        $currentStart = $this->getCarbonDate($this->start ?? now()->subDays(30)->startOfDay())->copy()->addDay();
        $currentEnd = $this->getCarbonDate($this->end ?? now()->endOfDay());
        $diff = round($currentStart->diffInDays($currentEnd));

        $previousStart = $currentStart->copy()->subDays($diff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        return cache()->remember($this->getCacheKeyProperty(), $this->getCacheDurationProperty(), fn (): array => [
            'current' => $this->getValue($currentStart, $currentEnd)->toArray(),
            'previous' => $this->getValue($previousStart, $previousEnd)->toArray(),
        ]);
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
