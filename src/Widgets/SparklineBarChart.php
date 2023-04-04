<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Support\Carbon;

class SparklineBarChart extends ChartWidget
{
    public function render()
    {
        return view('aura::components.widgets.sparkline-bar');
    }
}
