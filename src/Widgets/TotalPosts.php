<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;

class TotalPosts extends Widget
{
    public $component = 'aura::widgets.total_posts';

    public string $type = 'numeric';

    public function render()
    {
        return view('components.widgets.total-posts');
    }
}
