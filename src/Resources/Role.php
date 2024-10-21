<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Resource
{
    public array $actions = [
        'createMissingPermissions' => [
            'label' => 'Create Missing Permissions',
            'description' => 'Create missing permissions if you have added new resources.',
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
        'delete' => [
            'label' => 'Delete',
            'icon' => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'role';

    public static ?int $sort = 2;

    public static string $type = 'Role';

    public static bool $usesMeta = false;

    protected $casts = [
        'permissions' => 'array',
        'super_admin' => 'boolean',
    ];

    protected static $dropdown = 'Users';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'super_admin',
        'permissions',
        'team_id',
    ];

    protected static ?string $group = 'Aura';

    protected $table = 'roles';

    protected $with = [];

    public function createMissingPermissions()
    {
        GenerateAllResourcePermissions::dispatch();
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'searchable' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'based_on' => 'name',
                'custom' => false,
                'disabled' => true,
                'validation' => 'required',
                'on_index' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'type' => 'Aura\Base\Fields\Boolean',
                'instructions' => 'Super Admins have access to all permission and can manage other users.',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'live' => true,
                'default' => false,
            ],
            [
                'name' => 'Permissions',
                'on_index' => false,
                'type' => 'Aura\\Base\\Fields\\Permissions',
                'validation' => '',
                'conditional_logic' => function ($model, $form) {

                    if (optional(optional($form)['fields'])['super_admin']) {
                        return false;
                    }

                    return true;
                },
                'slug' => 'permissions',
                'resource' => 'Aura\\Base\\Resources\\Permission',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        return $this->hasMany(Meta::class, 'post_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_role')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function title()
    {
        if (isset($this->title)) {
            return $this->title." (#{$this->id})";
        } elseif (isset($this->name)) {
            return $this->name." (#{$this->id})";
        } else {
            return "Role (#{$this->id})";
        }
    }

    public function users(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->belongsToMany(User::class, 'user_role')
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps();
    }
}
