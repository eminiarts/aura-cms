<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $email
 * @property string $role
 * @property int $team_id
 */
class TeamInvitation extends Resource
{
    public static $createEnabled = false;

    public static ?string $slug = 'teaminvitation';

    public static string $type = 'teaminvitation';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static bool $showInNavigation = true;

    public static function getFields(): array
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
                'on_index' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon(): string
    {
        return view('aura::components.icon.team-invitation')->render();
    }

    public static function getShowInNavigation(): bool
    {
        return config('aura.teams') && static::$showInNavigation;
    }

    public function singularName(): string
    {
        return 'Team Invitation';
    }

    /**
     * Get the team that the invitation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
