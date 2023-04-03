<?php

namespace Eminiarts\Aura\Widgets;

use Carbon\CarbonInterval;
use Eminiarts\Aura\Resources\Post;

class TotalPosts extends Widget
{
    public $component = 'aura::widgets.total_posts';

    public string $type = 'numeric';

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public $widget;
    public $start;
    public $end;

    public function mount($widget)
    {
        $this->widget = $widget;
    }

    public function render()
    {
        return view('aura::components.widgets.total-posts');
    }

       public function updateDateRange($start, $end)
       {
           $this->start = $start;
           $this->end = $end;
       }
}
