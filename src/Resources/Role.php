<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\UserMetaPivot;

class Role extends Post
{
    public static ?string $slug = 'role';

    public static ?int $sort = 2;

    public static string $type = 'Role';

    protected static $dropdown = 'Users';

    protected $with = ['meta'];

    public static function getFields()
    {
        return [
            //             [
            //                 'name' => 'Role',
            //                 'slug' => 'role',
            //                 'type' => 'Eminiarts\\Aura\\Fields\\Tab',
            //                 'validation' => '',
            //                 'conditional_logic' => '',
            //                 'has_conditional_logic' => false,
            //                 'wrapper' => '',
            //                 'global' => true,
            //             ],

            [
                'name' => 'Name',
                'slug' => 'title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
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
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'type' => 'Eminiarts\Aura\Fields\Boolean',
                'instructions' => 'Super Admins have access to all permission and can manage other users.',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
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
                'posttype' => 'Eminiarts\\Aura\\Resources\\Permission',
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

    // public function users()
    // {
    //     return $this->hasManyThrough(User::class, UserMetaPivot::class, 'value', 'user_id', 'id')
    //         ->wherePivot('key', 'roles');
    // }
}
