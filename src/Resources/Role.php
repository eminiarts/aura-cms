<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Jobs\GenerateAllResourcePermissions;

class Role extends Resource
{
    public array $actions = [
        'createMissingPermissions' => [
            'label' => 'Create Missing Permissions',
            'description' => 'Create missing permissions if you have added new resources.',
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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

    public static ?string $slug = 'role';

    public static ?int $sort = 2;

    public static string $type = 'Role';

    protected static $dropdown = 'Users';

    protected $with = ['meta'];

    public function createMissingPermissions()
    {
        GenerateAllResourcePermissions::dispatch();
    }

    public function delete()
    {
        dd('delete');
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'type' => 'Eminiarts\Aura\Fields\Boolean',
                'instructions' => 'Super Admins have access to all permission and can manage other users.',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Permissions',
                'on_index' => false,
                'type' => 'Eminiarts\\Aura\\Fields\\Permissions',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'super_admin',
                        'operator' => '!=',
                        'value' => '1',
                    ],
                ],
                'slug' => 'permissions',
                'resource' => 'Eminiarts\\Aura\\Resources\\Permission',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_meta', 'value', 'user_id')
            ->wherePivot('key', 'roles');
    }
}
