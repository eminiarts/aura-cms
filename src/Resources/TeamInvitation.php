<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Team;

class TeamInvitation extends Resource
{
    public static string $type = 'Team Invitation';

    public static ?string $slug = 'teaminvitation';

    protected static $dropdown = 'Users';

    protected static bool $showInNavigation = false;

    protected static ?string $group = 'Aura';

    /**
     * Get the team that the invitation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'slug' => 'email',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Role',
                'slug' => 'role',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
