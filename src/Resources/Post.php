<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Widgets\AvgPostsNumber;
use Eminiarts\Aura\Widgets\PostChart;
use Eminiarts\Aura\Widgets\SumPostsNumber;
use Eminiarts\Aura\Widgets\TotalPosts;
use App\Models\Post as ModelsPost;

class Post extends ModelsPost
{
    public static string $type = 'Post';

    public static ?string $slug = 'post';

    public static $fields = [];

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [
            new TotalPosts(['width' => 'w-1/3']),
            new SumPostsNumber(['width' => 'w-1/3']),
            new AvgPostsNumber(['width' => 'w-1/3']),
            new PostChart(['width' => 'w-1/3']),
        ];
    }

    public static function getFields()
    {
        return [
            '.number' => [
                'id' => 3,
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => 'required',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                    0 => [
                        0 => [
                            'param' => '',
                            'operator' => '=',
                            'value' => '',
                        ],
                    ],
                ],
                'slug' => 'number',
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }
}
