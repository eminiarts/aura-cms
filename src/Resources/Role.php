<?php

namespace Eminiarts\Aura\Resources;

use App\Models\Post;

class Role extends Post
{
    public static string $type = 'Role';

    public static ?string $slug = 'role';

    public static ?string $group = 'Users';

    public static ?int $sort = 2;

    public static function getWidgets(): array
    {
        return [];
    }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
    }

    public static function getFields()
    {
        return [
            'role' => [
                'name' => 'Role',
                'slug' => 'role',
                'id' => 184,
                'type' => 'App\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            'role.slug' => [
                'label' => 'Title',
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => false,
                'slug' => 'slug',
                'style' => [
                    'width' => '100',
                ],
            ],
            'role.name' => [
                'name' => 'Name',
                'slug' => 'name',
                'id' => 151,
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            'role.description' => [
                'name' => 'Description',
                'slug' => 'description',
                'id' => 145,
                'type' => 'App\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            'layout' => [
                'name' => 'Permissions',
                'slug' => 'layout',
                'id' => 179,
                'type' => 'App\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            'permissions' => [
                'name' => 'Permissions',
                'slug' => 'permissions',
                'id' => 159,
                'posttype' => 'App\\Aura\\Resources\\Permission',
                'type' => 'App\\Aura\\Fields\\HasMany',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
        ];
    }
}
