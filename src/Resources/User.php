<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\UserFactory;
use Aura\Base\Resource;
use Aura\Base\Traits\ProfileFields;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Resource implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use Impersonate;
    use MustVerifyEmail;
    use Notifiable;
    use ProfileFields;
    use TwoFactorAuthenticatable;

    public static $customTable = true;

    public static bool $indexViewEnabled = true;

    public $preventPasswordUpdate = false;

    public static ?string $slug = 'user';

    public static ?int $sort = 1;

    public static string $type = 'User';

    public static bool $usesMeta = true;

    protected $appends = ['fields'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed',
    ];

    protected static $dropdown = 'Users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'fields', 'current_team_id', 'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token',
    ];

    protected static ?string $group = 'Admin';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected static array $searchable = ['name', 'email'];

    protected $table = 'users';

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->teams->contains(function ($t) use ($team) {
            return $t->id === $team->id;
        });
    }

    public function cachedRoles(): mixed
    {
        return $this->roles;

        return Cache::remember($this->getCacheKeyForRoles(), now()->addMinutes(60), function () {
            return $this->roles;
        });
    }

    public function canBeImpersonated()
    {
        return ! $this->resource->isSuperAdmin();
    }

    public function canImpersonate()
    {
        return $this->resource->isSuperAdmin();
    }

    public function clearCachedOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    // // Reset to default create Method from Laravel
    // public static function create($fields)
    // {
    //     $model = new static;

    //     return tap($model->newModelInstance($fields), function ($instance) {
    //         $instance->save();
    //     });
    // }

    /**
     * Get the current team of the user's context.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTeam()
    {
        if (! config('aura.teams')) {
            return;
        }

        if (is_null($this->current_team_id) && $this->id) {
            $team_id = $this->teams()->first()->id ?? null;

            if ($team_id) {
                $this->switchTeam($team_id);
            }
        }

        return $this->belongsTo(config('aura.resources.team'), 'current_team_id');
    }

    public function deleteOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);
    }

    public function getAvatarUrlAttribute()
    {
        return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';

        // Does not work atm
        if (! $this->fields['avatar']) {
            return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
        }

        // json decode the meta value
        $meta = is_string($this->fields['avatar']) ? json_decode($this->fields['avatar']) : $this->fields['avatar'];

        // get the attachment from the meta
        $attachments = Attachment::find($meta);

        if ($attachments && count($attachments) > 0) {
            $attachment = $attachments->first();

            return $attachment->path('thumbnail');
        }

        return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
    }

    // public function getEmailField($value)
    // {
    //     return "<a class='font-bold text-primary-500' href='mailto:".$value."'>".$value.'</a>';
    // }

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Avatar',
                'type' => 'Aura\\Base\\Fields\\Image',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'avatar',
                'style' => [
                    'width' => '100',
                ],
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
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Email',
                'validation' => 'required|email',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Current Team',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'searchable' => false,
                'slug' => 'current_team_id',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Roles',
                'slug' => 'roles',
                'resource' => 'Aura\\Base\\Resources\\Role',
                'type' => 'Aura\\Base\\Fields\\Roles',
                'multiple' => true,
                'polymorphic_relation' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],
            [
                'name' => 'Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['nullable', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
                'conditional_logic' => [],
                'slug' => 'password',
                'on_forms' => true,
                'on_edit' => true,
                'on_create' => true,
                'on_index' => false,
                'on_view' => false,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Teams',
                'slug' => 'tab-Teams',
                'global' => true,
                'conditional_logic' => function ($model, $post) {
                    return config('aura.teams');
                },
            ],
            [
                'name' => 'Teams',
                'slug' => 'teams',
                'type' => 'Aura\\Base\\Fields\\BelongsToMany',
                'resource' => 'Aura\\Base\\Resources\\Team',
                'validation' => '',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,

                'conditional_logic' => function ($model, $post) {
                    return config('aura.teams');
                },
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => '2FA',
                'label' => 'Tab',
                'slug' => '2fa-tab',
                'global' => true,
                'on_view' => false,
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],
        ];
    }

    public function getIcon()
    {
        return view('aura::components.icon.user')->render();
    }

    public function getInitials()
    {
        $name = $this->name;
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (strlen($initials) < 2) {
                $initials .= strtoupper(substr($word, 0, 1));
            } else {
                break;
            }
        }

        return $initials;
    }

    public function getMorphClass(): string
    {
        return "Aura\Base\Resources\User";
    }

    public function getOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {
            $o = substr($option, 0, -1);

            // Cache
            $options = Cache::remember($option, now()->addHour(), function () use ($o) {
                return Option::where('name', 'like', $o.'%')->get();
            });

            // Map the options, set the key to the option name (everything after last dot ".") and the value to the option value
            return $options->mapWithKeys(function ($item, $key) {
                return [str($item->name)->afterLast('.')->toString() => $item->value];
            });
        }

        // Cache
        $model = Cache::remember($option, now()->addHour(), function () use ($option) {
            return Option::whereName($option)->first();
        });

        if ($model) {
            return $model->value;
        }
    }

    public function getOptionBookmarks()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.bookmarks', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.bookmarks')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionColumns($slug)
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.columns.'.$slug, now()->addHour(), function () use ($slug) {
            return Option::whereName('user.'.$this->id.'.columns.'.$slug)->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebar()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebar', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebar')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebarToggled()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebarToggled', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebarToggled')->first();
        });

        if ($option) {
            return $option->value;
        }

        return true;
    }

    // public function getRolesField()
    // {
    //     return $this->roles->pluck('id')->toArray();
    // }

    public function getSearchableFields()
    {
        // get input fields and remove the ones that are not searchable
        $fields = $this->inputFields()->filter(function ($field) {
            // if $field is array or undefined, then we don't want to use it
            if (! is_array($field) || ! isset($field['searchable'])) {
                return false;
            }

            return $field['searchable'];
        });

        return $fields;
    }

    public function getTeams()
    {
        if (! config('aura.teams')) {
            return;
        }

        // Return cached teams with meta
        return Cache::remember('user.'.$this->id.'.teams', now()->addHour(), function () {
            return $this->teams()->with('meta')->get();
        });
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function hasAnyRole(array $roles): bool
    {
        $cachedRoles = $this->cachedRoles()->pluck('slug');

        if (! $cachedRoles) {
            return false;
        }

        foreach ($cachedRoles as $role) {
            if (in_array($role, $roles)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission($permission)
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }

            $permissions = $role->fields['permissions'];

            if (empty($permissions)) {
                continue;
            }

            foreach ($permissions as $p => $value) {
                if ($p == $permission && $value == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasPermissionTo($ability, $post): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }

            $permissions = $role->fields['permissions'];

            if (empty($permissions)) {
                continue;
            }

            // Temporary Fix. To Do: It should be an array
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true);
            }

            foreach ($permissions as $permission => $value) {
                if ($permission == $ability.'-'.$post::$slug && $value == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $r) {
            if ($r->slug == $role) {
                return true;
            }
        }

        return false;
    }

    public function indexQuery($query)
    {
        if (config('aura.teams')) {
            return $query->whereHas('roles', function ($query) {
                $query->where('roles.team_id', auth()->user()->current_team_id);
            });
        }

        return $query->whereHas('roles');
    }

    /**
     * Global Admin of Aura.
     */
    public function isAuraGlobalAdmin(): bool
    {
        return Gate::allows('AuraGlobalAdmin');
    }

    /**
     * Determine if the given team is the current team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function isCurrentTeam($team)
    {
        return $team->id === $this->currentTeam->id;
    }

    /**
     * Returns true if the user has at least one role that is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id == $team->{$this->getForeignKey()};
    }

    public function resource()
    {
        // Return \Aura\Base\Resources\User for this user
        if (config('aura.resources.user')) {
            return $this->hasOne(config('aura.resources.user'), 'id', 'id');
        } else {
            return $this->hasOne(\Aura\Base\Resources\User::class, 'id', 'id');
        }

        // Cache the resource so we don't have to query the database every time
        return Cache::remember('user.resource.'.$this->id, now()->addHour(), function () {
            return \Aura\Base\Resources\User::find($this->id);
        });
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->belongsToMany(Role::class, 'user_role')
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->belongsToMany(Role::class, 'user_role')
            // ->using(TeamUser::class)
            // ->withPivot('team_id')
            ->withTimestamps();
    }

    // public function setRolesField($value)
    // {
    //     // Save the roles
    //     if (config('aura.teams')) {
    //         $this->roles()->syncWithPivotValues($value, ['key' => 'roles', 'team_id' => $this->current_team_id]);
    //     } else {
    //         $this->roles()->syncWithPivotValues($value, ['key' => 'roles']);
    //     }

    //     // Unset the roles field
    //     unset($this->attributes['fields']['roles']);

    //     // Clear Cache 'user.' . $this->id . '.roles'
    //     Cache::forget('user.'.$this->id.'.roles');

    //     return $this;
    // }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * Get all of the teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_role')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function title()
    {
        return $this->name;
    }

    public function updateOption($option, $value)
    {
        $option = 'user.'.$this->id.'.'.$option;

        if (config('aura.teams')) {
            Option::updateOrCreate([
                'name' => $option,
                'team_id' => $this->current_team_id,
            ], ['value' => $value]);
        } else {
            Option::updateOrCreate([
                'name' => $option,
            ], ['value' => $value]);
        }

        // Clear the cache
        Cache::forget($option);
    }

    public function widgets()
    {
        return collect($this->getWidgets())->map(function ($item) {
            return $item;
        });
    }

    protected static function booted()
    {
        parent::booted();

        // static::saving(function ($user) {
        //     // If we marked to prevent password update, remove it from attributes
        //     if ($user->preventPasswordUpdate) {
        //         unset($user->attributes['password']);
        //         unset($user->preventPasswordUpdate);
        //         // $user->preventPasswordUpdate = false;
        //     }
        // });
    }

    protected function getCacheKeyForRoles(): string
    {
        return $this->current_team_id.'.user.'.$this->id.'.roles';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
