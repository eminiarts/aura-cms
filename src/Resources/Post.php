<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;
use Aura\Flows\Resources\Flow;
use Aura\Base\Widgets\PostChart;
use Aura\Base\Widgets\TotalPosts;
use Aura\Export\Traits\Exportable;
use Aura\Base\Widgets\AvgPostsNumber;
use Aura\Base\Widgets\SumPostsNumber;
use Aura\Base\Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Post extends Resource
{
    use Exportable;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Post?',
            'confirm-content' => 'Are you sure you want to delete this post?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
        'testAction' => [
            'label' => 'Test Action',
            'class' => 'hover:text-primary-700 text-primary-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Test Action Post?',
            'confirm-content' => 'Are you sure you want to test Action?',
            'confirm-button' => 'Yup',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
        'multipleExportSelected' => [
            'label' => 'Export',
            'modal' => 'export::export-selected-modal',
        ],
    ];

    public static $fields = [];

    public static ?string $slug = 'post';

    public static ?int $sort = 50;

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

    protected static ?string $group = 'Aura';

    protected $hidden = ['password'];

    protected static array $searchable = [
        'title',
        'content',
    ];

    public function callFlow($flowId)
    {
        $flow = Flow::find($flowId);
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
        return redirect()->route('aura.resource.index', [$this->getType()]);
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

        return $this->bulkActions;
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [
                ],
                'slug' => 'tab1',
            ],
            [
                'name' => 'Panel',
                'type' => 'Aura\\Base\\Fields\\Panel',
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
                'name' => 'ID',
                'slug' => 'id',
                'type' => 'Aura\\Base\\Fields\\ID',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => false,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Text',
                'slug' => 'text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
                'based_on' => 'title',
            ],
            [
                'name' => 'Bild',
                'type' => 'Aura\\Base\\Fields\\Image',
                'max' => 1,
                'upload' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'image',
            ],
            [
                'name' => 'Password for Test',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => 'nullable|min:8',
                'conditional_logic' => [
                ],
                'slug' => 'password',
                'hydrate' => function ($set, $model, $state, $get) {},
                'on_index' => false,
                'on_forms' => true,
                'on_view' => false,
            ],
            [
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Number',
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
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'date',
                'format' => 'y-m-d',
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
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
            //      'type' => 'Aura\\Base\\Fields\\Color',
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
                'type' => 'Aura\\Base\\Fields\\Panel',
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
                'type' => 'Aura\\Base\\Fields\\Tags',
                'model' => 'Aura\\Base\\Resources\\Tag',
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
                'type' => 'Aura\\Base\\Fields\\Tags',
                'model' => 'Aura\\Base\\Resources\\Category',
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
            //      'type' => 'Aura\\Base\\Fields\\BelongsTo',
            //      'resource' => 'Aura\\Base\\Resources\\Team',
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
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'resource' => 'Aura\\Base\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Attachments',
                'type' => 'Aura\\Base\\Fields\\Tab',
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
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\Attachment',
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
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
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
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
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
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
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
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
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
                'type' => 'Aura\\Base\\Widgets\\SparklineBar',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Sparkline Area',
                'slug' => 'sparkline_area',
                'type' => 'Aura\\Base\\Widgets\\SparklineArea',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Donut Chart',
                'slug' => 'donut',
                'type' => 'Aura\\Base\\Widgets\\Donut',
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
                'type' => 'Aura\\Base\\Widgets\\Pie',
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
                'type' => 'Aura\\Base\\Widgets\\Bar',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
        ];
    }

    public function indexTableSettings()
    {
        return [
            //     'default_view' => 'grid',
            // 'views' => [
            //         'grid' => 'custom.table.grid',
            //     ]
        ];
    }

    public function testAction() {}

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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
