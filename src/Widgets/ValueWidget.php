<?php

namespace App\Aura\Widgets;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ValueWidget extends Widget
{
    use Concerns\CanPoll;

    public $range;

    protected ?array $cachedCards = null;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'widgets.value';

    public function aggregate($model, $calculation, $column = null, $dateColumn = null)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $column = $column ?? $query->getModel()->getQualifiedKeyName();
        $dateColumn = $dateColumn ?? $query->getModel()->getQualifiedCreatedAtColumn();

        // dd($query->whereBetween($dateColumn, $this->previousRange)->{$calculation}($column));

        return [
            'current' => $current = with(clone $query)->whereBetween($dateColumn, $this->currentRange)->{$calculation}($column) ?? 0,
            'previous' => $previous = with(clone $query)->whereBetween($dateColumn, $this->previousRange)->{$calculation}($column) ?? 0,
            'increase' => $previous ? ($current - $previous) / $previous * 100 : null,
        ];
    }

    public function average($model, $column, $dateColumn = null)
    {
        return $this->aggregate($model, 'avg', $column, $dateColumn);
    }

    public function count($model, $column = null, $dateColumn = null)
    {
        return $this->aggregate($model, 'count', $column, $dateColumn);
    }

    public function getCurrentRangeProperty()
    {
        if ($this->range) {
            $range = $this->range;
        } else {
            $range = array_key_first($this->ranges());
        }

        return [
            CarbonImmutable::now()->subDays($range),
            CarbonImmutable::now()->subDays(),
        ];
    }

    public function getPreviousRangeProperty()
    {
        if ($this->range) {
            $range = $this->range;
        } else {
            $range = array_key_first($this->ranges());
        }

        return [
            CarbonImmutable::now()->subDays($range * 2),
            CarbonImmutable::now()->subDays($range)->subSecond(),
        ];
    }

    public function max($model, $column = null, $dateColumn = null)
    {
        return $this->aggregate($model, 'max', $column, $dateColumn);
    }

    public function min($model, $column = null, $dateColumn = null)
    {
        return $this->aggregate($model, 'min', $column, $dateColumn);
    }

    public function sum($model, $column = null, $dateColumn = null)
    {
        return $this->aggregate($model, 'sum', $column, $dateColumn);
    }
}
