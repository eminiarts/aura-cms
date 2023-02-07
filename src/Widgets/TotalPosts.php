<?php

namespace Eminiarts\Aura\Widgets;

use Eminiarts\Aura\Resources\Post;

class TotalPosts extends ValueWidget
{
    /**
     * @var string
     */
    public $name = 'Total Posts';

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
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function getValueProperty()
    {
        return $this->cache(function () {
            return $this->count(Post::class);
        });
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
            365 => '365 Days',
        ];
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'total-posts';
    }
}
