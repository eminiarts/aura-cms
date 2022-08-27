<?php

namespace Eminiarts\Aura\Widgets;

use App\Models\User;
use App\Aura\Resources\Post;

class AvgPostsNumber extends ValueWidget
{
    /**
    * @var string
    */
    public $name = "Avg Number";

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
            $query = Post::query()->leftJoin('post_meta', function ($join) {
                $join->on('posts.id', '=', 'post_meta.post_id')
            ->where('post_meta.key', '=', 'number');
            });

            return $this->average($query, 'post_meta.value');
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
        return 'avg-posts-number';
    }
}
