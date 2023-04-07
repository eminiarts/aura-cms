<?php

namespace Eminiarts\Aura\Resources;

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Widgets\AvgPostsNumber;
use Eminiarts\Aura\Widgets\PostChart;
use Eminiarts\Aura\Widgets\SumPostsNumber;
use Eminiarts\Aura\Widgets\TotalPosts;

class Post extends Resource
{
    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $fields = [];

    public static ?string $slug = 'post';

    public static ?int $sort = 0;

    public static string $type = 'Post';

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected static ?string $group = 'Posts';

    protected static array $searchable = [
        'title',
        'content',
    ];

    public function callFlow($flowId)
    {
        $flow = Flow::find($flowId);
        // dd('callManualFlow', $flow->name);
        $operation = $flow->operation;

        // Create a Flow Log
        $flowLog = $flow->logs()->create([
            'post_id' => $this->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($this, $flowLog->id);
    }

    public function delete()
    {
        // parent delete
        parent::delete();

        // redirect to index page
        return redirect()->route('aura.post.index', [$this->getType()]);
    }

    public function deleteSelected()
    {
        parent::delete();
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        $flows = Flow::where('trigger', 'manual')
            ->where('options->resource', $this->getType())
            ->get();

        foreach ($flows as $flow) {
            $this->bulkActions['callFlow.'.$flow->id] = $flow->name;
        }

        // dd($this->bulkActions);
        return $this->bulkActions;
    }

     public static function getFields()
     {
         return [
             [
                 'name' => 'Tab',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                 'validation' => '',
                 'on_index' => true,
                 'global' => true,
                 'conditional_logic' => [
                 ],
                 'slug' => 'tab1',
             ],
             [
                 'name' => 'Panel',
                 'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                 'validation' => '',
                 'on_index' => true,
                 'conditional_logic' => [
                 ],
                 'slug' => 'panel1',
                 'style' => [
                     'width' => '70',
                 ],
             ],
             [
                 'name' => 'Text',
                 'slug' => 'text',
                 'type' => 'Eminiarts\\Aura\\Fields\\Text',
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Slug for Test',
                 'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                 'validation' => 'required|alpha_dash',
                 'conditional_logic' => [
                 ],
                 'slug' => 'slug2',
                 'based_on' => 'text',
             ],
             [
                 'name' => 'Bild',
                 'type' => 'Eminiarts\\Aura\\Fields\\Image',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'image',
             ],
             [
                 'name' => 'Password for Test',
                 'type' => 'Eminiarts\\Aura\\Fields\\Password',
                 'validation' => 'nullable|min:8',
                 'conditional_logic' => [
                 ],
                 'slug' => 'password',
                 'on_index' => false,
                 'on_forms' => true,
                 'on_view' => false,
             ],
             [
                 'name' => 'Number',
                 'type' => 'Eminiarts\\Aura\\Fields\\Number',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'number',
                 'on_view' => true,
                 'on_forms' => true,
                 'on_index' => true,
             ],
             [
                 'name' => 'Date',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'date',
                 'format' => 'y-m-d',
             ],
             [
                 'name' => 'Description',
                 'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'description',
                 'style' => [
                     'width' => '100',
                 ],
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             //  [
             //      'name' => 'Color',
             //      'type' => 'Eminiarts\\Aura\\Fields\\Color',
             //      'validation' => '',
             //      'conditional_logic' => [
             //      ],
             //      'slug' => 'color',
             //      'on_index' => true,
             //      'on_forms' => true,
             //      'on_view' => true,
             //      'format' => 'hex',
             //  ],
             [
                 'name' => 'Sidebar',
                 'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                 'validation' => '',
                 'on_index' => true,
                 'conditional_logic' => [
                 ],
                 'slug' => 'sidebar',
                 'style' => [
                     'width' => '30',
                 ],
             ],
             [
                 'name' => 'Tags',
                 'slug' => 'tags',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                 'model' => 'Eminiarts\\Aura\\Taxonomies\\Tag',
                 'create' => true,
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Categories',
                 'slug' => 'categories',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                 'model' => 'Eminiarts\\Aura\\Taxonomies\\Category',
                 'create' => true,
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             //  [
             //      'name' => 'Team',
             //      'slug' => 'team_id',
             //      'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
             //      'resource' => 'Eminiarts\\Aura\\Resources\\Team',
             //      'validation' => '',
             //      'conditional_logic' => [
             //          [
             //              'field' => 'role',
             //              'operator' => '==',
             //              'value' => 'super_admin',
             //          ],
             //      ],
             //      'wrapper' => '',
             //      'on_index' => true,
             //      'on_forms' => true,
             //      'on_view' => true,
             //  ],
             [
                 'name' => 'User',
                 'slug' => 'user_id',
                 'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                 'resource' => 'Eminiarts\\Aura\\Resources\\User',
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Attachments',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                 'validation' => '',
                 'on_index' => true,
                 'global' => true,
                 'conditional_logic' => [
                 ],
                 'slug' => 'tab2',
             ],
             [
                 'name' => 'Attachments',
                 'slug' => 'attachments',
                 'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                 'resource' => 'Eminiarts\\Aura\\Resources\\Attachment',
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => false,
                 'on_forms' => true,
                 'on_view' => true,
                 'style' => [
                     'width' => '100',
                 ],
             ],
             [
                 'name' => 'Created at',
                 'slug' => 'created_at',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'enable_time' => true,
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Updated at',
                 'slug' => 'updated_at',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'enable_time' => true,
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
         ];
     }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Total Posts Created',
                'slug' => 'total_posts_created',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'count',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Average Number',
                'slug' => 'average_number',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'avg',
                'column' => 'number',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sum Number',
                'slug' => 'sum_number',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'sum',
                'column' => 'number',
                'goal' => 2000,
                'dailygoal' => false,
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sparkline Bar Chart',
                'slug' => 'sparkline_bar_chart',
                'type' => 'Eminiarts\\Aura\\Widgets\\SparklineBar',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Sparkline Area',
                'slug' => 'sparkline_area',
                'type' => 'Eminiarts\\Aura\\Widgets\\SparklineArea',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Donut Chart',
                'slug' => 'donut',
                'type' => 'Eminiarts\\Aura\\Widgets\\Donut',
                'cache' => 300,
                // 'values' => function () {
                //     return [
                //         'value1' => 10,
                //         'value2' => 20,
                //         'value3' => 30,
                //     ];
                // },
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Pie Chart',
                'slug' => 'pie',
                'type' => 'Eminiarts\\Aura\\Widgets\\Pie',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Bar Chart',
                'slug' => 'bar',
                'type' => 'Eminiarts\\Aura\\Widgets\\Bar',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
        ];
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         // new TotalPosts(['width' => 'w-full md:w-1/3']),
    //         // new SumPostsNumber(['width' => 'w-full md:w-1/3']),
    //         // new AvgPostsNumber(['width' => 'w-full md:w-1/3']),
    //         new PostChart(['width' => 'w-full md:w-1/3']),
    //     ];
    // }

    public function title()
    {
        return optional($this)->title." (Post #{$this->id})";
    }
}
