<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class TeamInvitation extends Resource
{
    public static ?string $slug = 'teaminvitation';

    public static string $type = 'teaminvitation';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static bool $showInNavigation = true;

    public function singularName()
    {
        return "Team Invitation";
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>';
    }

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
