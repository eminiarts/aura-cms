<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Traits\CustomTable;
use Eminiarts\Aura\Jobs\RunOperation;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Illuminate\Support\Facades\Log;

class Operation extends Post
{
    use CustomTable;

    public static ?string $slug = 'operation';

    public static ?int $sort = 3;

    public static string $type = 'Operation';

    public static bool $usesMeta = false;

    protected $casts = [
        'options' => 'json',
        'start_at' => 'datetime',
        'finished_at' => 'datetime',
        'request' => 'json',
        'response' => 'json',
    ];

    protected static $dropdown = 'Flows';

    protected $fillable = [
        'name', 'key', 'flow_id', 'resolve_id', 'reject_id', 'type', 'status', 'options', 'user_id', 'team_id', 'fields',
    ];

    protected $table = 'flow_operations';

    protected static bool $title = false;

    // Custom Fields Collection
    public function fieldsCollection()
    {
        // Merge fields from type
        if (optional($this)->type) {
            return collect(array_merge(app($this->type)->getFields(), $this->getFields()));
        }

        return collect($this->getFields());
    }

    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Operation details',
                'slug' => 'operation-tab',
                'global' => true,
            ],
            [
                'name' => 'Operation Infos',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'operation-infos',
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
                'name' => 'Key',
                'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                'based_on' => 'name',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'key',
            ],
            [
                'name' => 'Flow_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'disabled' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'flow_id',
            ],
            [
                'name' => 'Resolve_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'disabled' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'resolve_id',
            ],
            [
                'name' => 'Reject_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'disabled' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'reject_id',
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
            // [
            //     'name' => 'Options',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Code',
            //     'validation' => '',
            //     'conditional_logic' => [
            //     ],
            //     'on_index' => false,
            //     'slug' => 'options',
            //     'language' => 'json',
            // ],

            [
                'name' => 'User_id',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'user_id',
            ],

            // [
            //     'type' => 'Eminiarts\\Aura\\Fields\\Tab',
            //     'name' => 'OperationLogs',
            //     'slug' => 'tab-OperationLogs',
            //     'global' => true,
            // ],
            // [
            //     'name' => 'OperationLogs',
            //     'slug' => 'flow_operation_logs',
            //     'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
            //     'posttype' => 'Eminiarts\\Aura\\Resources\\OperationLog',
            //     'validation' => '',
            //     'conditional_logic' => '',
            //     'has_conditional_logic' => false,
            //     'wrapper' => '',
            //     'on_index' => false,
            //     'on_forms' => true,
            //     'in_view' => true,
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

        ];
    }

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 12C16 14.2091 14.2091 16 12 16C9.79085 16 7.99999 14.2091 7.99999 12M16 12C16 9.79086 14.2091 8 12 8C9.79085 8 7.99999 9.79086 7.99999 12M16 12H22M7.99999 12H2.00018" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function logs()
    {
        return $this->hasMany(OperationLog::class);
    }

    public function reject()
    {
        return $this->belongsTo(Operation::class, 'reject_id');
    }

    public function resolve()
    {
        return $this->belongsTo(Operation::class, 'resolve_id');
    }

    public function run($post, $flowLogId, $data = null)
    {
        // If $this->type does not exist, throw an exception
        if (! app($this->type)) {
            throw new \Exception('Operation type not found - '.$this->type);
        }

        // If $this->type is Delay, dispatch the job with a delay
        if ($this->type == 'Eminiarts\\Aura\\Operations\\Delay') {
            // dd('in delay run', $this->name, $this->type);

            // Log $this->options['delay']
            // Log::info('Delaying operation for '.$this->options['delay'].' seconds');

            return dispatch(new RunOperation($this, $post, $flowLogId, $data))->delay(now()->addSeconds($this->options['delay']));
        }

        // Dispatch RunOperation
        dispatch(new RunOperation($this, $post, $flowLogId, $data));
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

            // dd('before saving', $post);

            // unset post attributes
            // unset($post->title);
            // unset($post->content);
            // unset($post->team_id);
        });
    }
}
