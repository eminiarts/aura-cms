<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Traits\CustomTable;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\Scopes\TeamScope;

class Flow extends Post
{
    use CustomTable;

    public static ?string $slug = 'flow';

    public static ?int $sort = 2;

    public static string $type = 'Flow';

    public static bool $usesMeta = false;

    protected $casts = [
        'options' => 'json',
        'data' => 'json',
    ];

    protected static $dropdown = 'Flows';

    protected $fillable = [
        'name', 'trigger', 'options', 'data', 'status', 'team_id', 'operation_id', 'user_id', 'team_id', 'fields',
    ];

    protected $table = 'flows';

    protected static bool $title = false;

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Flow',
                'label' => 'Tab',
                'slug' => 'flow-tab',
                'global' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'CreateFlow',
                'type' => 'Eminiarts\\Aura\\Fields\\LivewireComponent',
                'component' => 'create-flow',
                'create' => false,
                'on_index' => false,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'create-flow',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'details-tab',
                'global' => true,
            ],
            [
                'name' => 'Flow Infos',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'flow-infos',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Trigger',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'trigger',
                'options' => [
                    [
                        'value' => 'Post Hook',
                        'key' => 'post',
                    ],
                    [
                        'value' => 'Webhook',
                        'key' => 'webhook',
                    ],
                    [
                        'value' => 'Schedule',
                        'key' => 'schedule',
                    ],
                    [
                        'value' => 'Flow',
                        'key' => 'flow',
                    ],
                    [
                        'value' => 'Manual',
                        'key' => 'manual',
                    ],
                ],
            ],
            // [
            //     'name' => 'Options',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Code',
            //     'validation' => '',
            //     'on_index' => false,
            //     'conditional_logic' => [
            //     ],
            //     'slug' => 'options',
            //     'language' => 'json',
            // ],
            [
                'name' => 'Data',
                'type' => 'Eminiarts\\Aura\\Fields\\Code',
                'on_index' => false,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'data',
                'language' => 'php',
            ],
            [
                'name' => 'Options',
                'type' => 'Eminiarts\\Aura\\Fields\\Code',
                'on_index' => false,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'options',
                'language' => 'json',
            ],

            [
                'name' => 'Status',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'status',
                'options' => [
                    [
                        'value' => 'Active',
                        'key' => 'active',
                    ],
                    [
                        'value' => 'Inactive',
                        'key' => 'inactive',
                    ],
                ],
            ],
            [
                'name' => 'Operation_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'operation_id',
            ],
            [
                'name' => 'User_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'user_id',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'FlowLogs',
                'slug' => 'tab-FlowLogs',
                'global' => true,
            ],
            [
                'name' => 'FlowLogs',
                'slug' => 'flowLogs',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'posttype' => 'Eminiarts\\Aura\\Resources\\FlowLog',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4V15.2C12 16.8802 12 17.7202 12.327 18.362C12.6146 18.9265 13.0735 19.3854 13.638 19.673C14.2798 20 15.1198 20 16.8 20H17M17 20C17 21.1046 17.8954 22 19 22C20.1046 22 21 21.1046 21 20C21 18.8954 20.1046 18 19 18C17.8954 18 17 18.8954 17 20ZM7 4L17 4M7 4C7 5.10457 6.10457 6 5 6C3.89543 6 3 5.10457 3 4C3 2.89543 3.89543 2 5 2C6.10457 2 7 2.89543 7 4ZM17 4C17 5.10457 17.8954 6 19 6C20.1046 6 21 5.10457 21 4C21 2.89543 20.1046 2 19 2C17.8954 2 17 2.89543 17 4ZM12 12H17M17 12C17 13.1046 17.8954 14 19 14C20.1046 14 21 13.1046 21 12C21 10.8954 20.1046 10 19 10C17.8954 10 17 10.8954 17 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function logs()
    {
        return $this->hasMany(FlowLog::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    public function operations()
    {
        return $this->hasMany(Operation::class);
    }

    public function subFields($key)
    {
        $fields = [
            'webhook' => [
                [
                    'name' => 'Webhook',
                    'type' => 'Eminiarts\\Aura\\Fields\\Select',
                    'validation' => '',
                    'conditional_logic' => [
                    ],
                    'slug' => 'trigger',
                    'options' => [
                        [
                            'value' => 'Post',
                            'key' => 'post',
                        ],
                        [
                            'value' => 'Get',
                            'key' => 'get',
                        ],
                    ],
                ],
            ],

            'manual' => [
                [
                    'name' => 'Resource',
                    'type' => 'Eminiarts\\Aura\\Fields\\Text',
                    'validation' => 'required',
                    'on_index' => true,
                    'slug' => 'name',
                    'style' => [
                        'width' => '100',
                    ],
                ],

            ],
        ];

        return $fields[$key] ?? [];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function title()
    {
        return $this->name;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TeamScope());

        static::saving(function ($post) {
            if (! $post->team_id && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            if (! $post->user_id && auth()->user()) {
                $post->user_id = auth()->user()->id;
            }

            // unset post attributes
            unset($post->title);
            unset($post->content);
            unset($post->type);
            // unset($post->team_id);
        });
    }
}
