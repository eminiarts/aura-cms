<?php

namespace Aura\Base\Resources;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

class Permission extends Resource
{
    public static $globalSearch = false;

    public static ?string $slug = 'permission';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static ?int $sort = 3;

    protected static bool $title = false;

    protected static string $type = 'Permission';

    public static function getFields()
    {
        return [

            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Group',
                'slug' => 'group',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [],
                'options' => [
                    'Invoice' => 'Invoice',
                    'Permission' => 'Permission',
                    'Post' => 'Post',
                    'Project' => 'Project',
                    'Role' => 'Role',
                    'User' => 'User',
                ],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }

    public static function getGroupOptions()
    {
        return collect(Aura::getResources())->mapWithKeys(fn ($item) => [$item => $item])->toArray();
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" /></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
