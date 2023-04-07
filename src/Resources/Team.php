<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Database\Factories\TeamFactory;
use Eminiarts\Aura\Models\TeamMeta;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Traits\SaveFieldAttributes;
use Eminiarts\Aura\Traits\SaveMetaFields;
use Eminiarts\Aura\Traits\SaveTerms;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Team extends Resource
{
    use HasFactory;
    use SaveFieldAttributes;
    use SaveMetaFields;
    use SaveTerms;

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'team';

    public static string $type = 'Team';

    protected $fillable = [
        'name', 'user_id', 'fields',
    ];

    protected $table = 'teams';

    protected static bool $title = false;

    public function customPermissions()
    {
        return [
            'invite-users' => 'Invite users to team',
        ];
    }

    public static function getFields()
    {
        return [

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Team',
                'slug' => 'tab-team',
                'global' => true,
            ],
            [
                'name' => 'Team',
                'slug' => 'team-panel',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'on_index' => true,
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Users',
                'slug' => 'tab-users',
                'global' => true,
                'on_create' => false,
            ],
            [
                'name' => 'Users',
                'slug' => 'users',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                    'class' => '!p-4',
                ],
            ],
            [
                'name' => 'Invitations',
                'slug' => 'tab-Invitations',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'global' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_create' => false,
            ],
            [
                'name' => 'Invitations',
                'slug' => 'Invitations',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\TeamInvitation',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                    'class' => '!p-4',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 21V15.6C15 15.0399 15 14.7599 14.891 14.546C14.7951 14.3578 14.6422 14.2049 14.454 14.109C14.2401 14 13.9601 14 13.4 14H10.6C10.0399 14 9.75992 14 9.54601 14.109C9.35785 14.2049 9.20487 14.3578 9.10899 14.546C9 14.7599 9 15.0399 9 15.6V21M19 21V6.2C19 5.0799 19 4.51984 18.782 4.09202C18.5903 3.71569 18.2843 3.40973 17.908 3.21799C17.4802 3 16.9201 3 15.8 3H8.2C7.07989 3 6.51984 3 6.09202 3.21799C5.71569 3.40973 5.40973 3.71569 5.21799 4.09202C5 4.51984 5 5.0799 5 6.2V21M21 21H3M9.5 8H9.51M14.5 8H14.51M10 8C10 8.27614 9.77614 8.5 9.5 8.5C9.22386 8.5 9 8.27614 9 8C9 7.72386 9.22386 7.5 9.5 7.5C9.77614 7.5 10 7.72386 10 8ZM15 8C15 8.27614 14.7761 8.5 14.5 8.5C14.2239 8.5 14 8.27614 14 8C14 7.72386 14.2239 7.5 14.5 7.5C14.7761 7.5 15 7.72386 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function meta()
    {
        return $this->hasMany(TeamMeta::class, 'team_id');
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }

    public function title()
    {
        return $this->name;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_meta', 'team_id', 'user_id')->wherePivot('key', 'roles');
    }

    protected static function booted()
    {
        static::saving(function ($team) {
            // unset title attribute
            unset($team->title);
            unset($team->content);
            unset($team->type);
            unset($team->team_id);

            if (! $team->user_id && auth()->user()) {
                $team->user_id = auth()->user()->id;
            }
        });

        static::creating(function ($team) {
            // dd('creating', $team);
        });

        static::created(function ($team) {
            $user = auth()->user();

            // Change the current team id of the user
            $user->current_team_id = $team->id;
            $user->save();

            // Create a Super Admin role for the team
            $role = Role::create([
                'type' => 'Role',
                'title' => 'Super Admin',
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Super Admin has can perform everything.',
                'super_admin' => true,
                'permissions' => [],
                'user_id' => $user->id,
            ]);

            // Attach the current user to the team
            $team->users()->attach($user->id, [
                'key' => 'roles',
                'value' => json_encode([$role->id]),
            ]);

            // Clear cache of Cache('user.'.$this->id.'.teams')
            Cache::forget('user.'.$user->id.'.teams');
        });

        // static::updating(function ($team) {
        //     dd('uppdating');
        // });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TeamFactory::new();
    }
}
