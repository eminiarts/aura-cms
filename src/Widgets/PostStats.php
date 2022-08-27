<?php

namespace Eminiarts\Aura\Widgets;

use App\Aura\Resources\Post;

class PostStats extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Posts', Post::count()),
            // Card::make('Product Inventory', Post::sum('qty')),
            // Card::make('Average price', number_format(Post::avg('price'), 2)),
        ];
    }
}
