<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class TeamInvitation extends Resource
{
    public static $createEnabled = false;

    public static ?string $slug = 'teaminvitation';

    public static string $type = 'teaminvitation';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static bool $showInNavigation = true;

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

    public function getIcon()
    {
        return view('aura::components.icon.team-invitation')->render();
    }

    public function singularName()
    {
        return 'Team Invitation';
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
