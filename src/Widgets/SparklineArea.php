<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Support\Carbon;

class SparklineArea extends Sparkline
{
    public function render()
    {
        return view('aura::components.widgets.sparkline-area');
    }
}
