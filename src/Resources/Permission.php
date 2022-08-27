<?php

namespace Eminiarts\Aura\Resources;

use App\Models\Post;

class Permission extends Post
{
    protected static string $type = 'Permission';

    protected static ?string $slug = 'permission';

    protected static ?string $group = 'Users';

    protected static ?int $sort = 3;

    protected static bool $title = false;

    public static function getWidgets(): array
    {
        return [];
    }

    public function getTitleAttribute()
    {
        dd('heer');
        return $this->email;
    }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>';
    }

    public static function getFields()
    {
        return [
            'permission' => [
                'name' => 'Permission',
                'slug' => 'permission',
                'type' => 'App\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            'permission.name' => [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            'permission.slug' => [
                'label' => 'Title',
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'slug',
                'style' => [
                    'width' => '100',
                ],
            ],
            'permission.description' => [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'App\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            'permission.group' => [
                'name' => 'Group',
                'slug' => 'group',
                'id' => 174,
                'type' => 'App\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => '',
                'choices' => [
                    'Invoice' => 'Invoice',
                    'Permission' => 'Permission',
                    'Post' => 'Post',
                    'Project' => 'Project',
                    'Role' => 'Role',
                    'User' => 'User',
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
        ];
    }

    public static function getGroupChoices()
    {
        return app('App\Aura')->resources()->mapWithKeys(fn ($item) => [ $item => $item])->toArray();
    }
}
