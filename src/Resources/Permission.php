<?php

namespace Aura\Base\Resources;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

class Permission extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'permission';

    public static bool $usesMeta = false;

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static ?int $sort = 3;

    protected $table = 'permissions';

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
        return view('aura::components.icon.permission')->render();
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
