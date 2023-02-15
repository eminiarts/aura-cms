<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Widgets\PostStats;

class TeamInvitation extends Post
{
    public static $singularName = 'Team Invitation';

    public static ?string $slug = 'TeamInvitation';

    public static string $type = 'TeamInvitation';

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Invoice',
                'slug' => 'tab-invoice',
                'global' => true,
            ],

            [
                'name' => 'Email',
                'slug' => 'email',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'validation' => 'required|email|unique:users,email',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Role',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'role',
                'options' => Role::get()->pluck('title', 'id')->toArray(),
            ],
        ];
    }
}
