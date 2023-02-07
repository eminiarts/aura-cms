<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;

class Permission extends Post
{
    public static ?string $slug = 'permission';

    protected static $dropdown = 'Users';

    protected static ?int $sort = 3;

    protected static bool $title = false;

    protected static string $type = 'Permission';

    public static function getFields()
    {
        return [

            [
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
            [
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'slug',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'App\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            [
                'name' => 'Group',
                'slug' => 'group',
                'type' => 'App\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => '',
                'options' => [
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

    public static function getGroupOptions()
    {
        return app('App\Aura')->resources()->mapWithKeys(fn ($item) => [$item => $item])->toArray();
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
