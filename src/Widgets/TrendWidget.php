<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TrendWidget extends Widget
{
    use Concerns\CanPoll;

    public $range;

    protected ?array $cachedCards = null;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'widgets.trend';

    public function countByDays($model, $range, $column = null)
    {
        return $this->count($model, 'day', $column, $range);
    }

    /**
     * Return a value result showing a count aggregate over time.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $unit
     * @param  string|null  $column
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function count($model, $unit, $dateColumn, $range)
    {
        $resource = $model instanceof Builder ? $model->getModel() : new $model();

        $dateColumn = $dateColumn ?? $resource->getQualifiedCreatedAtColumn();

        return $this->aggregate($model, $unit, 'count', $resource->getQualifiedKeyName(), $dateColumn, $range);
    }

    /**
     * Determine the proper aggregate starting date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $unit
     * @param  mixed  $timezone
     * @return \Carbon\CarbonInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getAggregateStartingDate($unit)
    {
        $now = CarbonImmutable::now();

        $range = $this->range;
        $ranges = collect($this->ranges())->keys()->values()->all();

        if (count($ranges) > 0 && ! in_array($range, $ranges)) {
            $range = min($range, max($ranges));
        }

        switch ($unit) {
            case 'month':
                return $now->subMonthsWithoutOverflow($range - 1)->firstOfMonth()->setTime(0, 0);

            case 'week':
                return $now->subWeeks($range - 1)->startOfWeek()->setTime(0, 0);

            case 'day':
                return $now->subDays($range - 1)->setTime(0, 0);

            case 'hour':
                return with($now->subHours($range - 1), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':00');
                });

            case 'minute':
                return with($now->subMinutes($range - 1), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':'.$now->minute.':00');
                });

            default:
                throw new InvalidArgumentException('Invalid trend unit provided.');
        }
    }

    /**
     * Format the possible aggregate result date into a proper string.
     *
     * @param  \Carbon\CarbonInterface  $date
     * @param  string  $unit
     * @param  bool  $twelveHourTime
     * @return string
     */
    protected function formatPossibleAggregateResultDate(CarbonInterface $date, $unit)
    {
        switch ($unit) {
            case 'month':
                return __($date->format('F')).' '.$date->format('Y');

            case 'week':
                return __($date->startOfWeek()->format('F')).' '.$date->startOfWeek()->format('j').' - '.
                       __($date->endOfWeek()->format('F')).' '.$date->endOfWeek()->format('j');

            case 'day':
                return $date->toDateString();

            case 'hour':
                return  __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:00');

            case 'minute':
            default:
                return __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:i');
        }
    }

    protected function aggregate($model, $unit, $function, $column, $dateColumn, $range)
    {
        $query = $model instanceof Builder ? $model : (new $model())->newQuery();

        $dateColumn = $dateColumn ?? $query->getModel()->getQualifiedCreatedAtColumn();

        $wrappedColumn = $column instanceof Expression
                ? (string) $column
                : $query->getQuery()->getGrammar()->wrap($column);

        $expression = "DATE({$dateColumn})";

        // dd($dateColumn, $expression, $function, $wrappedColumn, $column);
        $possibleDateResults = $this->getAllPossibleDateResults(
            $range[0],
            $range[1],
            $unit
        );

        $results = $query
                ->select(DB::raw("{$expression} as date_result, {$function}({$wrappedColumn}) as aggregate"))
                ->tap(function ($query) {
                    return $query;
                    // Do we need Query Filters?
                    // return $this->applyFilterQuery($query);
                })
                ->whereBetween($dateColumn, $range)
                ->groupBy(DB::raw($expression))
                ->orderBy('date_result')
                ->get()->mapWithKeys(function ($item) {
                    // Rounding ? - round($result->aggregate, $this->roundingPrecision, $this->roundingMode)]
                    return[$item['date_result'] => $item['aggregate']];
                })->all();

        $results = array_merge($possibleDateResults, $results);

        return collect($results);
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

    /**
     * Get all of the possible date results for the given units.
     *
     * @param  \Carbon\CarbonInterface  $startingDate
     * @param  \Carbon\CarbonInterface  $endingDate
     * @param  string  $unit
     * @param  bool  $twelveHourTime
     * @return array<string, int>
     */
    protected function getAllPossibleDateResults(
        CarbonInterface $startingDate,
        CarbonInterface $endingDate,
        $unit
    ) {
        $nextDate = Carbon::instance($startingDate);

        $possibleDateResults[$this->formatPossibleAggregateResultDate(
            $nextDate,
            $unit
        )] = 0;

        while ($nextDate->lt($endingDate)) {
            if ($unit === 'month') {
                $nextDate->addMonthWithOverflow();
            } elseif ($unit === 'week') {
                $nextDate->addWeek();
            } elseif ($unit === 'day') {
                $nextDate->addDay();
            } elseif ($unit === 'hour') {
                $nextDate->addHour();
            } elseif ($unit === 'minute') {
                $nextDate->addMinute();
            }

            if ($nextDate->lte($endingDate)) {
                $possibleDateResults[
                    $this->formatPossibleAggregateResultDate(
                        $nextDate,
                        $unit,
                    )
                ] = 0;
            }
        }

        return $possibleDateResults;
    }
}
