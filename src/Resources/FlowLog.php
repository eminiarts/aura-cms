<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Traits\CustomTable;

class FlowLog extends Post
{
    use CustomTable;

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static bool $showInNavigation = false;

    public static ?string $slug = 'flowlog';

    public static string $type = 'FlowLog';

    protected static $dropdown = 'Flows';

    // Fillable
    protected $fillable = [
        'flow_id', 'status', 'started_at', 'finished_at', 'request', 'response', 'options', 'user_id', 'team_id',
    ];

    protected $table = 'flow_logs';

    public function deleteSelected()
    {
        $this->delete();
    }

    public static function getFields()
    {
        return [

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'FlowLog',
                'slug' => 'status',
                'global' => true,
            ],

            [
                'name' => 'Finished_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'finished_at',
            ],
            [
                'name' => 'Started_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'started_at',
            ],
            [
                'name' => 'Flow',
                'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                'validation' => '',
                'on_index' => true,
                'slug' => 'flow',
                'model' => 'Eminiarts\\Aura\\Resources\\Flow',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Status',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'status',
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
                'name' => 'Response',
                'type' => 'Eminiarts\\Aura\\Fields\\Code',
                'on_index' => false,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'response',
                'language' => 'json',
            ],
            [
                'name' => 'Request',
                'type' => 'Eminiarts\\Aura\\Fields\\Code',
                'on_index' => false,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'request',
                'language' => 'json',
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'OperationLogs',
                'slug' => 'tab-OperationLogs',
                'global' => true,
            ],
            [
                'name' => 'OperationLogs',
                'slug' => 'flow_operation_logs',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'posttype' => 'Eminiarts\\Aura\\Resources\\OperationLog',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3V13.2C3 14.8802 3 15.7202 3.32698 16.362C3.6146 16.9265 4.07354 17.3854 4.63803 17.673C5.27976 18 6.11984 18 7.8 18H15M15 18C15 19.6569 16.3431 21 18 21C19.6569 21 21 19.6569 21 18C21 16.3431 19.6569 15 18 15C16.3431 15 15 16.3431 15 18ZM3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
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
