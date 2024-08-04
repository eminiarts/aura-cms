<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;
use Aura\Base\Models\TeamMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Aura\Base\Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Team extends Resource
{
    use SoftDeletes;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public static $customMeta = true;

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'team';

    public static string $type = 'Team';

    protected $fillable = [
        'name', 'user_id', 'fields',
    ];

    protected static ?string $group = 'Global';

    protected $table = 'teams';

    protected static bool $title = false;

    public function clearCachedOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    public function customPermissions()
    {
        return [
            'invite-users' => 'Invite users to team',
        ];
    }

    public function deleteOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);
    }

    public static function getFields()
    {
        return [

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Team',
                'slug' => 'tab-team',
                'global' => true,
            ],
            [
                'name' => 'Team',
                'slug' => 'team-panel',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => true,
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
            ],
            // [
            //     'type' => 'Aura\\Base\\Fields\\Tab',
            //     'name' => 'Users',
            //     'slug' => 'tab-users',
            //     'global' => true,
            //     'on_create' => false,
            // ],
            // [
            //     'name' => 'Users',
            //     'slug' => 'users',
            //     'type' => 'Aura\\Base\\Fields\\HasMany',
            //     'resource' => 'Aura\\Base\\Resources\\User',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_index' => false,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '100',
            //         'class' => '!p-4',
            //     ],
            // ],
            // [
            //     'name' => 'Invitations',
            //     'slug' => 'tab-Invitations',
            //     'type' => 'Aura\\Base\\Fields\\Tab',
            //     'global' => true,
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_create' => false,
            // ],
            // [
            //     'name' => 'Invitations',
            //     'slug' => 'Invitations',
            //     'type' => 'Aura\\Base\\Fields\\HasMany',
            //     'resource' => 'Aura\\Base\\Resources\\TeamInvitation',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_index' => false,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '100',
            //         'class' => '!p-4',
            //     ],
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /> </svg>';
    }

    public function getOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {

            $o = substr($option, 0, -1);

            // Cache
            $options = Option::where('name', 'like', $o.'%')->orderBy('id')->get();

            // Map the options, set the key to the option name (everything after last dot ".") and the value to the option value
            return $options->mapWithKeys(function ($item, $key) {
                return [str($item->name)->afterLast('.')->toString() => $item->value];
            });
        }

        $model = Option::whereName($option)->first();

        if ($model) {
            return $model->value;
        }
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

    public function updateOption($option, $value)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::updateOrCreate(['name' => $option], ['value' => $value]);

        Cache::forget($option);
    }

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'post_relations', 'team_id', 'roleable_id')
    //         ->where('roleable_type', User::class);
    // }
    // public function users()
    // {
    //     return $this->hasManyThrough(Role::class, User::class);
    // }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Role::class,
            'team_id', // Foreign key on roles table...
            'id', // Foreign key on users table...
            'id', // Local key on teams table...
            'id' // Local key on roles table...
        )->distinct() // To avoid duplicate users
         ->join('post_relations', function ($join) {
             $join->on('post_relations.resource_id', '=', 'roles.id')
                  ->where('post_relations.resource_type', '=', Role::class)
                  ->where('post_relations.related_type', '=', User::class);
         })
         ->where('post_relations.related_id', '=', DB::raw('users.id'));
    }

       public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
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

            if ($user = auth()->user()) {
                // Change the current team id of the user
                // $user->switchTeam($team);
                $user->current_team_id = $team->id;
                $user->save();
            }

            // Create a Super Admin role for the team
            $role = Role::create([
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Super Admin has can perform everything.',
                'super_admin' => true,
                'permissions' => [],
                'team_id' => $team->id,
            ]);

            // Attach the current user to the team
            if ($user) {
                // $role->users()->sync([$user->id]);
                $role->users()->sync([$user->id => ['resource_type' => Role::class]]);

                // Clear cache of Cache('user.'.$user->id.'.teams')
                Cache::forget('user.'.$user->id.'.teams');
            }
        });

        static::deleted(function ($team) {
            // Get all users who had the deleted team as their current team
            $users = User::where('current_team_id', $team->id)->get();

            // Loop through the users and update their current_team_id
            foreach ($users as $user) {
                $firstTeam = $user->teams()->first();
                $user->current_team_id = $firstTeam ? $firstTeam->id : null;
                $user->save();
            }

            // Delete all the team's roles
            // $team->roles()->delete();

            // Delete all the team's metas
            $team->meta()->delete();

            // Delete all the team's invitations
            $team->teamInvitations()->delete();

            // Delete all the team's options
            Option::where('name', 'like', 'team.'.$team->id.'.%')->delete();

            // Clear cache of Cache('user.'.$this->id.'.teams')
            Cache::forget('user.'.auth()->user()->id.'.teams');

            // Redirect to the dashboard
            return redirect()->route('aura.dashboard');
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
