<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class TeamInvitation extends Resource
{
    public static ?string $slug = 'teaminvitation';

    public static string $type = 'Team Invitation';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static bool $showInNavigation = false;

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'slug' => 'email',
                'type' => 'Aura\\Base\\Fields\\Email',
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
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    /**
     * Get the team that the invitation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
