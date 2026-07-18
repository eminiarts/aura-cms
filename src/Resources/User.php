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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        'global_admin' => 'boolean',
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

    /**
     * Per-instance memo of resolved (shadow-applied) roles, keyed by
     * "{teamId|global}:{Role::catalogVersion()}".
     *
     * @var array<string, Collection>
     */
    protected array $resolvedRolesCache = [];

    protected static array $searchable = ['name', 'email'];

    protected $table = 'users';

    // public static $showActionsAsButtons = true;

    public function actions()
    {
        return [
            'delete' => [
                'label' => 'Delete',
                'icon-view' => 'aura::components.actions.trash',
                'class' => 'hover:text-red-700 text-red-500 font-bold',
            ],
            'impersonateAction' => [
                'label' => 'Impersonate',
                'icon-view' => 'aura::components.actions.impersonate',
                'conditional_logic' => function () {
                    return auth()->user()->isAuraGlobalAdmin();
                },
            ],
        ];
    }

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

    /**
     * The user's effective (shadow-resolved) roles in the current team context.
     *
     * This is the User-side entry to the Role Catalog resolution seam. Each of
     * the user's Memberships is resolved by slug through Role::resolveForTeam:
     * a Team Role (Shadow) wins over the Global Role of the same slug. Pivot
     * rows are never rewritten.
     *
     * The result is memoized per instance and keyed by team context + catalog
     * version, so repeated permission checks in a request (policies fire this
     * per row/action) stay query-free, while creating/deleting a Shadow bumps
     * the version and forces a recompute — instant shadow effect, no per-call
     * queries.
     */
    public function cachedRoles(): mixed
    {
        if (! $this->id) {
            return collect();
        }

        $teamId = config('aura.teams') ? $this->current_team_id : null;
        $cacheKey = ($teamId ?? 'global').':'.Role::catalogVersion();

        if (array_key_exists($cacheKey, $this->resolvedRolesCache)) {
            return $this->resolvedRolesCache[$cacheKey];
        }

        // Read the raw Membership rows for the relevant team context. The team
        // filter is strict: in Teams-on mode a non-null current team reads only
        // that team's Memberships, and a null current team reads only Memberships
        // with a null pivot team_id — never an unfiltered read across all teams
        // (which would leak roles from teams the user is not currently in). In
        // Teams-off mode the pivot has no team_id column, so it is a flat read.
        $roleIds = DB::table('user_role')
            ->where('user_id', $this->id)
            ->when(
                config('aura.teams'),
                fn ($query) => $teamId
                    ? $query->where('team_id', $teamId)
                    : $query->whereNull('team_id')
            )
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return $this->resolvedRolesCache[$cacheKey] = collect();
        }

        // The Membership's identity is its role slug; resolve each slug through
        // the catalog seam. Bypass scopes to read slugs of global rows too.
        $slugs = Role::withoutGlobalScopes()
            ->whereIn('id', $roleIds)
            ->pluck('slug')
            ->unique();

        return $this->resolvedRolesCache[$cacheKey] = $slugs
            ->map(fn ($slug) => Role::resolveForTeam($slug, $teamId))
            ->filter()
            ->unique('id')
            ->values();
    }

    public function canBeImpersonated()
    {
        return ! $this->isAuraGlobalAdmin();
    }

    public function canImpersonate()
    {
        return $this->isAuraGlobalAdmin();
    }

    public function clearCachedOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    public static function clearCurrentTeamCache(string|int|null $userId): void
    {
        if (! $userId) {
            return;
        }

        Cache::forget(static::currentTeamCacheKey($userId));
    }

    /**
     * Get the current team of the user's context.
     *
     * @return BelongsTo
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

    public static function currentTeamCacheKey(string|int $userId): string
    {
        return "user_{$userId}_current_team_id";
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
    }

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
                'on_create' => false,
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
                    'width' => '50',
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
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Current Team',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'on_forms' => false,
                'searchable' => false,
                'slug' => 'current_team_id',
            ],
            [
                'name' => 'Role',
                'slug' => 'roles',
                'resource' => 'Aura\\Base\\Resources\\Role',
                'type' => 'Aura\\Base\\Fields\\Roles',
                'multiple' => false,
                'polymorphic_relation' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'style' => [
                    'width' => '50',
                ],
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
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Global Admin',
                'slug' => 'global_admin',
                'type' => 'Aura\\Base\\Fields\\GlobalAdmin',
                'instructions' => 'Instance-level operator with access across all teams. Only a Global Admin can grant or revoke this.',
                'validation' => '',
                'default' => false,
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                // Client-advisory only: the field is shown to Global Admins so
                // they can toggle it. The authoritative guard lives server-side
                // in the GlobalAdmin field's saved() escalation check.
                'conditional_logic' => function ($model, $post) {
                    return auth()->check() && Gate::allows('AuraGlobalAdmin');
                },
                'style' => [
                    'width' => '50',
                ],
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
                'on_forms' => false,
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
                'component' => 'aura::two-factor-authentication-form',
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

            $permissions = $this->normalizePermissions($role);

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

            $permissions = $this->normalizePermissions($role);

            if (empty($permissions)) {
                continue;
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

    public function impersonateAction()
    {
        $this->impersonate($this);
    }

    public function indexQuery($query)
    {
        if (config('aura.teams')) {
            // A user belongs to the current team when they hold a Membership for
            // it — filter on the pivot's team_id, not the role row's team_id. A
            // Global Role carries team_id = null, so keying off the role row would
            // wrongly exclude members who hold a Global Role (e.g. global admin).
            return $query->whereHas('roles', function ($query) {
                $query->where('user_role.team_id', Auth::user()->current_team_id);
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

    /**
     * Clear the resolved-roles memo when the model is refreshed, so a pivot
     * attached/detached directly followed by refresh() reflects fresh roles
     * (the staleness semantics existing tests rely on).
     */
    public function refresh()
    {
        $this->resolvedRolesCache = [];

        return parent::refresh();
    }

    public function resource()
    {
        // Return \Aura\Base\Resources\User for this user
        if (config('aura.resources.user')) {
            return $this->hasOne(config('aura.resources.user'), 'id', 'id');
        } else {
            return $this->hasOne(User::class, 'id', 'id');
        }

        // Cache the resource so we don't have to query the database every time
        return Cache::remember('user.resource.'.$this->id, now()->addHour(), function () {
            return User::find($this->id);
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

        static::saved(function ($user) {
            if ($user->wasChanged('current_team_id')) {
                static::clearCurrentTeamCache($user->id);
            }
        });

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

    /**
     * Normalize a role's permission set into an array.
     *
     * The single place that reconciles permission sets stored as a cast array
     * or as a JSON string (meta/field values can deliver either), so permission
     * checks behave identically regardless of how the set was persisted.
     */
    protected function normalizePermissions($role): array
    {
        $permissions = $role->permissions;

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        return is_array($permissions) ? $permissions : [];
    }
}
