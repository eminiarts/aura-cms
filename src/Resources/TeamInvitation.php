<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Resource;

class TeamInvitation extends Resource
{
    public static $globalSearch = false;

    public static $singularName = 'Team Invitation';

    public static ?string $slug = 'TeamInvitation';

    public static string $type = 'TeamInvitation';

    protected static bool $showInNavigation = false;

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'slug' => 'email',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'validation' => 'required|email|unique:users,email',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Role',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'role',
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function getRoleOptions()
    {
        return Role::get()->pluck('title', 'id')->toArray();
    }
}
