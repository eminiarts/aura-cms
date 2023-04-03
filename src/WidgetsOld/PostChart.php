<?php

namespace Eminiarts\Aura\Widgets;

use Eminiarts\Aura\Resources\Post;

class PostChart extends TrendWidget
{
    /**
     * @var string
     */
    public $name = 'Total Posts';

    protected static string $view = 'aura::widgets.chart';

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        return now()->addMinutes(5);
    }

    /**
     * Calculate the value of the metric.
     *
     * @return mixed
     */
    public function getCurrentProperty()
    {
        return $this->cache(function () {
            return $this->countByDays(Post::class, $this->currentRange);
        }, 'current');
    }

    public function getPreviousProperty()
    {
        return $this->cache(function () {
            return $this->countByDays(Post::class, $this->previousRange);
        }, 'previous');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'post-chart';
    }
}
