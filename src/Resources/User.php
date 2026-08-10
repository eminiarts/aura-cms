<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\UserFactory;
use Aura\Base\Exceptions\OptionOwnerIdentityException;
use Aura\Base\Models\Meta;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\TeamUser;
use Aura\Base\Resource;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Aura\Base\Services\VersionedCache;
use Aura\Base\Traits\HasTeamMemberships;
use Aura\Base\Traits\ProfileFields;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Cache\FailoverStore;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use Lab404\Impersonate\Models\Impersonate;
use Lab404\Impersonate\Services\ImpersonateManager;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

class User extends Resource implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use HasTeamMemberships;
    use Impersonate;
    use MustVerifyEmail;
    use Notifiable;
    use ProfileFields;
    use TwoFactorAuthenticatable;

    /**
     * The gate that decides Global Admin status. Host apps may redefine it.
     */
    public const GLOBAL_ADMIN_GATE = 'AuraGlobalAdmin';

    /** Legacy key cleared during writes for upgrade compatibility. */
    public const GLOBAL_ADMIN_TEAMS_CACHE_KEY = 'aura.global_admin.teams';

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
     * "{connection}:{teamId|global}:{Role::catalogVersion()}".
     *
     * @var array<string, Collection>
     */
    protected array $resolvedRolesCache = [];

    protected static array $searchable = ['name', 'email'];

    protected $table = 'users';

    /** @var array<string, string> */
    private static array $currentTeamProcessEpochs = [];

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
                'ability' => 'update',
                'icon-view' => 'aura::components.actions.impersonate',
                'conditional_logic' => function () {
                    return auth()->user()?->isAuraGlobalAdmin();
                },
            ],
        ];
    }

    public static function authenticatedResource(): ?self
    {
        $authenticatedUser = auth()->user();

        if ($authenticatedUser instanceof self) {
            return $authenticatedUser;
        }

        $resource = optional($authenticatedUser)->resource;

        return $resource instanceof self ? $resource : null;
    }

    public function authorizedCurrentTeam(): ?Team
    {
        $currentTeamId = $this->getAttribute('current_team_id');

        if (! config('aura.teams') || $currentTeamId === null) {
            return null;
        }

        if ($this->relationLoaded('currentTeam')
            && (string) $this->getRelation('currentTeam')?->getKey() !== (string) $currentTeamId) {
            $this->unsetRelation('currentTeam');
        }

        $team = $this->currentTeam;

        if (! $team instanceof Team || (string) $team->getKey() !== (string) $currentTeamId) {
            return null;
        }

        return $this->isAuraGlobalAdmin() || $this->teams()->whereKey($team->getKey())->exists() ? $team : null;
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if ($this->getKey() === null
            || $this->getKey() === ''
            || ! $this->isTeamOnOwnConnection($team)
        ) {
            return false;
        }

        return $this->teams()
            ->useWritePdo()
            ->whereKey($team->getKey())
            ->exists();
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
        if ($this->getKey() === null || $this->getKey() === '') {
            return collect();
        }

        $connection = $this->getConnection();
        $connectionName = $connection->getName();
        $teamId = $this->currentTeamIdForAuthorization();
        $cacheKey = static::connectionCacheIdentity($connection)
            .':'.($teamId ?? 'global')
            .':'.Role::catalogVersion($connection);

        if (array_key_exists($cacheKey, $this->resolvedRolesCache)) {
            return $this->resolvedRolesCache[$cacheKey];
        }

        // Read the raw Membership rows for the relevant team context. The team
        // filter is strict: in Teams-on mode a non-null current team reads only
        // that team's Memberships, and a null current team reads only Memberships
        // with a null pivot team_id — never an unfiltered read across all teams
        // (which would leak roles from teams the user is not currently in). In
        // Teams-off mode the pivot has no team_id column, so it is a flat read.
        $roleIds = $connection->table('user_role')
            ->useWritePdo()
            ->where('user_id', $this->id)
            ->when(
                config('aura.teams'),
                fn ($query) => $teamId !== null && $teamId !== ''
                    ? $query->where('team_id', $teamId)
                    : $query->whereNull('team_id')
            )
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return $this->resolvedRolesCache[$cacheKey] = collect();
        }

        // The Membership's identity is its role slug; resolve each slug through
        // the catalog seam. Bypass scopes to read slugs of global rows too.
        $slugs = Role::on($connectionName)
            ->withoutGlobalScopes()
            ->useWritePdo()
            ->whereIn('id', $roleIds)
            ->pluck('slug')
            ->unique();

        return $this->resolvedRolesCache[$cacheKey] = $slugs
            ->map(fn ($slug) => Role::resolveForTeam($slug, $teamId, $connection))
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
        $this->forgetOptionCache($option);
    }

    public static function clearCurrentTeamCache(
        string|int|null $userId,
        ?Connection $connection = null,
    ): void {
        if ($userId === null || $userId === '') {
            return;
        }

        TeamScope::invalidateCurrentTeamId($userId, $connection);
    }

    public static function clearGlobalAdminTeamsCache(?Connection $connection = null): void
    {
        VersionedCache::bump(self::globalAdminTeamsCacheNamespace($connection), $connection);
        Cache::forget(self::globalAdminTeamsCacheKey($connection));
        Cache::forget(self::GLOBAL_ADMIN_TEAMS_CACHE_KEY);
    }

    public static function clearLegacyOptionCacheForTeam(
        string|int $userId,
        string|int $teamId,
        ?Connection $connection = null,
    ): void {
        VersionedCache::bump(self::legacyOptionCacheNamespaceFor($userId, $teamId, $connection), $connection);
        VersionedCache::bump(self::unscopedLegacyOptionCacheNamespaceFor($userId, $teamId), $connection);
    }

    public static function clearOptionCacheForScope(
        string|int $teamId,
        ?Connection $connection = null,
    ): void {
        VersionedCache::bump(self::optionCacheNamespaceForScope($teamId, $connection), $connection);
    }

    public static function clearOptionCacheForTeam(
        string|int $userId,
        string|int $teamId,
        ?Connection $connection = null,
    ): void {
        self::clearOptionCacheForScope($teamId, $connection);
        self::clearLegacyOptionCacheForTeam($userId, $teamId, $connection);
    }

    public static function clearTeamsCache(string|int|null $userId, ?Connection $connection = null): void
    {
        if ($userId === null || $userId === '') {
            return;
        }

        VersionedCache::bump(self::teamsCacheNamespace($userId, $connection), $connection);
        Cache::forget(self::teamListCacheKey($userId, $connection));
        Cache::forget('user.'.$userId.'.teams');
    }

    public static function connectionCacheIdentity(?Connection $connection = null): string
    {
        $connection ??= DB::connection();

        return hash('sha256', implode("\0", [
            (string) $connection->getName(),
            $connection->getDriverName(),
            (string) $connection->getDatabaseName(),
            (string) $connection->getConfig('host'),
            (string) $connection->getConfig('port'),
            (string) $connection->getConfig('username'),
            (string) $connection->getConfig('schema'),
            $connection->getTablePrefix(),
        ]));
    }

    public static function connectionScopedCacheKey(
        string $key,
        ?Connection $connection = null,
    ): string {
        return 'aura_connection_'.static::connectionCacheIdentity($connection).'.'.$key;
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

        $this->setAttribute('current_team_id', $this->currentTeamIdForAuthorization());

        if (is_null($this->current_team_id)
            && $this->getKey() !== null
            && $this->getKey() !== ''
        ) {
            // Fall back to the user's first Membership. A Global Admin with no
            // Membership has no team here — current team stays null and they
            // operate by visiting a team explicitly via switchTeam().
            $team = $this->teams()->useWritePdo()->first();

            if ($team) {
                $this->switchTeam($team);
            }
        }

        /** @var Model $team */
        $team = app(config('aura.resources.team'));
        $team = $team->newInstance();
        $team->setConnection($this->getConnectionName());

        return $this->newBelongsTo(
            $team->newQuery()->useWritePdo(),
            $this,
            'current_team_id',
            $team->getKeyName(),
            'currentTeam',
        );
    }

    public static function currentTeamCacheEpoch(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        self::ensureCurrentTeamCacheStoreIsCoherent();

        $epochKey = self::currentTeamCacheEpochKey($userId, $connection);
        $epoch = Cache::get($epochKey);

        if (is_string($epoch) && $epoch !== '') {
            unset(self::$currentTeamProcessEpochs[$epochKey]);

            return $epoch;
        }

        if (array_key_exists($epochKey, self::$currentTeamProcessEpochs)) {
            return self::$currentTeamProcessEpochs[$epochKey];
        }

        $candidate = Str::random(40);

        Cache::add($epochKey, $candidate);

        $epoch = Cache::get($epochKey);

        if (! is_string($epoch) || $epoch === '') {
            Cache::forever($epochKey, $candidate);

            $epoch = Cache::get($epochKey);

            if (! is_string($epoch) || $epoch === '') {
                return self::$currentTeamProcessEpochs[$epochKey] = $candidate;
            }

            unset(self::$currentTeamProcessEpochs[$epochKey]);
        }

        return $epoch;
    }

    public static function currentTeamCacheKey(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        $connection ??= DB::connection();
        $connectionIdentity = static::connectionCacheIdentity($connection);
        $epoch = static::currentTeamCacheEpoch($userId, $connection);

        return "aura_current_team_{$connectionIdentity}_user_{$userId}_epoch_{$epoch}";
    }

    public function currentTeamIdForAuthorization(): int|string|null
    {
        if (! config('aura.teams')) {
            return null;
        }

        $contextTeamId = TeamScope::currentContextTeamId($this->getConnection());

        if ($contextTeamId !== null) {
            return $contextTeamId;
        }

        return TeamScope::currentTeamIdForUser($this);
    }

    public function deleteOption($option)
    {
        $option = (string) $option;
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($option): void {
            $records = $this->optionQuery()
                ->whereIn('name', $this->optionNames($option))
                ->lockForUpdate()
                ->get();

            $records = $records->map(fn (Option $record): Option => $this->verifiedOptionRecord($record));

            foreach ($records as $record) {
                $this->requireSuccessfulOptionMutation($record->delete());
            }
        });

        $this->forgetOptionCache($option, $connection);
    }

    public function deleteOptionForTeam(string $option, string|int|null $teamId): void
    {
        $this->optionUserForTeam($teamId)->deleteOption($option);
    }

    public static function flushCurrentTeamCacheState(): void
    {
        self::$currentTeamProcessEpochs = [];
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
                // Case-insensitive uniqueness: reject an email that already
                // belongs to another user (any casing), instead of surfacing a
                // raw DB unique-constraint 500. The Edit form ignores the current
                // record automatically (see Edit::ignoreCurrentModelInRule).
                'validation' => ['required', 'email', new CaseInsensitiveUniqueEmail],
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
                'type' => 'Aura\\Base\\Fields\\UserTeams',
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
        $option = (string) $option;

        if (! $this->hasAuthorizedOptionContext()) {
            return str_ends_with($option, '*') ? collect() : null;
        }

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (str_ends_with($option, '*')) {
            $wildcardPrefix = substr($option, 0, -1);

            $payload = VersionedCache::remember(
                $this->optionCacheNamespace(),
                $this->optionCacheVariant($option),
                now()->addHour(),
                fn (): array => [
                    'values' => $this->optionConnection()->transaction(
                        fn (): array => $this->resolveWildcardOptionValues($wildcardPrefix),
                    ),
                ],
                $this->optionConnection(),
            );

            return collect($payload['values']);
        }

        return $this->getOptionEntry($option)['value'];
    }

    public function getOptionBookmarks()
    {
        return $this->optionValueOrDefault('bookmarks', []);
    }

    public function getOptionColumns($slug)
    {
        return $this->optionValueOrDefault('columns.'.$slug, []);
    }

    /**
     * @return array{found: bool, value: mixed}
     */
    public function getOptionEntry($option): array
    {
        $option = (string) $option;

        if (! $this->hasAuthorizedOptionContext()) {
            return ['found' => false, 'value' => null];
        }

        return $this->resolveOptionEntry($option);
    }

    /**
     * Read an option for an explicit team context without consulting auth().
     *
     * This is a low-level storage adapter. Callers remain responsible for
     * authorizing access to the requested preference context.
     *
     * @return array{found: bool, value: mixed}
     */
    public function getOptionEntryForTeam(string $option, string|int|null $teamId): array
    {
        return $this->optionUserForTeam($teamId)->resolveOptionEntry($option);
    }

    public function getOptionSidebar()
    {
        return $this->optionValueOrDefault('sidebar', []);
    }

    public function getOptionSidebarToggled()
    {
        return $this->optionValueOrDefault('sidebarToggled', true);
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

        $connection = $this->getConnection();

        // A Global Admin sees every team in the switcher, not only the teams they
        // are a member of — visitation lets them enter any of them. The list is
        // identical for every Global Admin, so it uses one shared generation
        // namespace that Team writes invalidate (see Team::booted).
        if ($this->isAuraGlobalAdmin()) {
            return $this->rememberTeams(self::globalAdminTeamsCacheNamespace($connection), function () use ($connection) {
                $team = app(config('aura.resources.team'));
                $team->setConnection($connection->getName());

                return $team->newQuery()
                    ->withoutGlobalScope(TeamScope::class)
                    ->with('meta')
                    ->get();
            });
        }

        // Return cached teams with meta
        return $this->rememberTeams(self::teamsCacheNamespace($this->id, $connection), function () {
            return $this->teams()->with('meta')->get();
        });
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function globalAdminTeamsCacheKey(?Connection $connection = null): string
    {
        return static::connectionScopedCacheKey(static::GLOBAL_ADMIN_TEAMS_CACHE_KEY, $connection);
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
        // The row action is invoked on the TARGET user ($this); the acting user
        // is the impersonator. Authorize the pair explicitly — Lab404's take()
        // does not — so only a Global Admin may impersonate (canImpersonate), and
        // a Global Admin can never be impersonated (canBeImpersonated). The actor
        // side goes through the gate because auth()->user() is loosely typed.
        $impersonator = auth()->user();

        abort_unless(
            $impersonator
                && Gate::forUser($impersonator)->allows(self::GLOBAL_ADMIN_GATE)
                && $this->canBeImpersonated(),
            403
        );

        app(ImpersonateManager::class)->take($impersonator, $this);
    }

    public function indexQuery($query)
    {
        if (config('aura.teams')) {
            // A Global Admin transcends the tenant boundary: the Users index
            // lists every user — including users with no Membership at all
            // (e.g. orphaned by a team deletion), who must stay reachable for
            // an instance operator. (TeamScope's users branch grants the
            // matching cross-team bypass; both seams must relax together or
            // the index would re-restrict.) The gate is consulted directly since
            // the authenticated user is loosely typed here.
            if (Auth::check() && Gate::allows(self::GLOBAL_ADMIN_GATE)) {
                return $query;
            }

            // A user belongs to the current team when they hold a Membership for
            // it — filter on the pivot's team_id, not the role row's team_id. A
            // Global Role carries team_id = null, so keying off the role row would
            // wrongly exclude members who hold a Global Role (e.g. global admin).
            return $query->whereHas('roles', function ($query) {
                $authenticatedUser = Auth::user();
                $currentTeamId = $authenticatedUser instanceof self
                    ? $authenticatedUser->currentTeamIdForAuthorization()
                    : null;

                $query->where('user_role.team_id', $currentTeamId);
            });
        }

        return $query->whereHas('roles');
    }

    /**
     * Global Admin of Aura.
     */
    public function isAuraGlobalAdmin(): bool
    {
        // Evaluate the gate for THIS user instance, not the authenticated user —
        // policies, switchTeam, and impersonation call this on a specific model
        // that is not always the current actor.
        return Gate::forUser($this)->allows(self::GLOBAL_ADMIN_GATE);
    }

    /**
     * Determine if the given team is the current team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function isCurrentTeam($team)
    {
        // A Global Admin may have no current team (no Membership, not yet
        // visiting) while the switcher still lists teams to enter — guard the
        // null so the switcher renders instead of dereferencing a null team.
        return $this->currentTeam && $team->id === $this->currentTeam->id;
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

    public static function optionNameBelongsToUser(string $name, string|int $userId): bool
    {
        return str_starts_with($name, self::optionNamePrefixFor($userId))
            || str_starts_with($name, self::versionTwoOptionNamePrefixFor($userId))
            || (is_int($userId) && str_starts_with($name, 'user.'.$userId.'.'));
    }

    public static function optionNamePrefixFor(string|int $userId): string
    {
        return 'u'.VersionedCache::compactIdentity('option.user.owner', $userId);
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
        }

        return $this->hasOne(User::class, 'id', 'id');
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->teamMembershipsToMany(Role::class, 'user_id', 'role_id', 'roles')
                ->using(TeamUser::class)
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->teamMembershipsToMany(Role::class, 'user_id', 'role_id', 'roles')
            ->using(TeamUser::class)
            ->withTimestamps();
    }

    public static function rotateCurrentTeamCacheEpoch(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        self::ensureCurrentTeamCacheStoreIsCoherent();

        $epochKey = self::currentTeamCacheEpochKey($userId, $connection);
        $epoch = Str::random(40);

        Cache::forever($epochKey, $epoch);

        if (Cache::get($epochKey) === $epoch) {
            unset(self::$currentTeamProcessEpochs[$epochKey]);
        } else {
            self::$currentTeamProcessEpochs[$epochKey] = $epoch;
        }

        return $epoch;
    }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        // Switching the current team is a teams-only operation — a no-op in
        // Teams-off mode (there is no teams table to switch between).
        if (! config('aura.teams')) {
            return false;
        }

        if (! $this->isTeamOnOwnConnection($team) || Option::isEveryoneTeamId($team->getKey())) {
            return false;
        }

        // Visitation: a Global Admin may enter any team without holding a
        // Membership (no user_role row is created — switchTeam only moves the
        // current-team pointer). Their in-team power comes from the policy gate
        // bypasses, not from resolved roles. Everyone else must be a member.
        if (! $this->belongsToTeam($team) && ! $this->isAuraGlobalAdmin()) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    public static function teamListCacheKey(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        return static::connectionScopedCacheKey('user.'.$userId.'.teams', $connection);
    }

    /**
     * Get all of the teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->teamMembershipsToMany(Team::class, 'user_id', 'team_id', 'teams')
            ->using(TeamUser::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function title()
    {
        return $this->name;
    }

    public function updateOption($option, $value)
    {
        $option = (string) $option;
        $connection = $this->optionConnection();
        $record = $connection->transaction(fn (): Option => $this->persistOption($option, $value));

        $this->forgetOptionCache($option, $record->getConnection());
    }

    public function updateOptionForTeam(string $option, mixed $value, string|int|null $teamId): void
    {
        $this->optionUserForTeam($teamId)->updateOption($option, $value);
    }

    public function widgets()
    {
        return collect($this->getWidgets())->map(function ($item) {
            return $item;
        });
    }

    /**
     * @return Builder<Option>
     */
    protected function activeOptionQuery(): Builder
    {
        $option = new Option;
        $query = $this->optionQuery()->withoutGlobalScopes();

        if ($this->optionConnection()->getSchemaBuilder()->hasColumn($option->getTable(), $option->getDeletedAtColumn())) {
            $query->whereNull($option->getQualifiedDeletedAtColumn());
        }

        return $query;
    }

    protected function assertOptionOwnerIdentity(mixed $ownerIdentity, string|int|null $optionId = null): void
    {
        if (! is_string($ownerIdentity) || ! hash_equals($this->optionOwnerIdentity(), $ownerIdentity)) {
            throw OptionOwnerIdentityException::forOption($optionId);
        }
    }

    protected static function booted()
    {
        parent::booted();

        static::saving(function (User $user): void {
            if (config('aura.teams') && Option::isEveryoneTeamId($user->getAttribute('current_team_id'))) {
                throw new InvalidArgumentException('Team ID 0 is reserved for everyone preferences.');
            }
        });

        static::saved(function ($user) {
            if ($user->wasChanged('current_team_id')) {
                static::clearCurrentTeamCache($user->id, $user->getConnection());
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

    protected function forgetOptionCache(string $option, ?Connection $connection = null): void
    {
        $connection ??= $this->optionConnection();

        VersionedCache::bump($this->optionCacheNamespace($connection), $connection);
        VersionedCache::bump($this->legacyOptionCacheNamespace($connection), $connection);
        VersionedCache::bump(
            self::unscopedLegacyOptionCacheNamespaceFor(
                $this->id,
                config('aura.teams') ? ($this->current_team_id ?? 'none') : 'global',
            ),
            $connection,
        );
    }

    protected function getCacheKeyForRoles(): string
    {
        return static::connectionScopedCacheKey(
            $this->currentTeamIdForAuthorization().'.user.'.$this->id.'.roles',
            $this->getConnection(),
        );
    }

    protected static function globalAdminTeamsCacheNamespace(?Connection $connection = null): string
    {
        return 'teams.global-admin.'.static::connectionCacheIdentity($connection);
    }

    protected function hasAuthorizedOptionContext(): bool
    {
        if (! config('aura.teams')) {
            return true;
        }

        $team = static::authenticatedResource()?->authorizedCurrentTeam();

        return $team !== null
            && (string) $team->getKey() === (string) $this->getAttribute('current_team_id');
    }

    protected function legacyOptionCacheNamespace(?Connection $connection = null): string
    {
        $teamId = config('aura.teams') ? ($this->current_team_id ?? 'none') : 'global';

        return self::legacyOptionCacheNamespaceFor($this->id, $teamId, $connection ?? $this->optionConnection());
    }

    protected static function legacyOptionCacheNamespaceFor(
        string|int $userId,
        string|int $teamId,
        ?Connection $connection = null,
    ): string {
        return 'option.user.'.static::connectionCacheIdentity($connection).'.'.$userId.'.team.'.$teamId;
    }

    protected function legacyOptionName(string $option): string
    {
        return 'user.'.$this->id.'.'.$option;
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

    protected function optionCacheNamespace(?Connection $connection = null): string
    {
        $teamId = config('aura.teams') ? ($this->current_team_id ?? 'none') : 'global';

        return self::optionCacheNamespaceForScope($teamId, $connection ?? $this->optionConnection());
    }

    protected static function optionCacheNamespaceForScope(
        string|int $teamId,
        ?Connection $connection = null,
    ): string {
        return 'option.users.v2.'.VersionedCache::identity(
            'option.users.scope',
            static::connectionCacheIdentity($connection),
            $teamId,
        );
    }

    protected function optionCacheVariant(string $option): string
    {
        return VersionedCache::identity('option.user.key.v3', $this->getKey(), $option);
    }

    protected function optionConnection(): Connection
    {
        return $this->optionQuery()->getModel()->getConnection();
    }

    protected function optionName(string $option): string
    {
        return $this->optionNamePrefix().$option;
    }

    protected function optionNamePrefix(): string
    {
        return static::optionNamePrefixFor($this->getKey());
    }

    /**
     * Canonical name followed by every readable migration alias.
     *
     * @return array<int, string>
     */
    protected function optionNames(string $option): array
    {
        $names = [
            $this->optionName($option),
            static::versionTwoOptionNamePrefixFor($this->getKey()).$option,
        ];

        if ($this->readsLegacyOptionNames()) {
            $names[] = $this->legacyOptionName($option);
        }

        return array_values(array_unique($names));
    }

    protected function optionOwnerIdentity(): string
    {
        return VersionedCache::identity('option.user.owner', $this->getKey());
    }

    /**
     * @return Builder<Option>
     */
    protected function optionQuery(): Builder
    {
        $query = Option::on($this->getConnectionName());

        if (! config('aura.teams')) {
            return $query;
        }

        return $query
            ->withoutGlobalScope(TeamScope::class)
            ->where('team_id', $this->getAttribute('current_team_id'));
    }

    protected function optionUserForTeam(string|int|null $teamId): static
    {
        $user = clone $this;
        $user->setAttribute('current_team_id', config('aura.teams') ? $teamId : null);
        $user->unsetRelation('currentTeam');

        return $user;
    }

    protected function optionValueOrDefault(string $option, mixed $default): mixed
    {
        $entry = $this->getOptionEntry($option);

        return $entry['found'] ? $entry['value'] : $default;
    }

    protected function persistOption(string $option, mixed $value): Option
    {
        $optionName = $this->optionName($option);
        $record = $this->optionQuery()
            ->withTrashed()
            ->where('name', $optionName)
            ->lockForUpdate()
            ->first();

        if ($record === null) {
            foreach (array_slice($this->optionNames($option), 1) as $legacyName) {
                $record = $this->optionQuery()
                    ->withTrashed()
                    ->where('name', $legacyName)
                    ->lockForUpdate()
                    ->first();

                if ($record !== null) {
                    break;
                }
            }
        }

        if ($record !== null) {
            $record = $this->verifiedOptionRecord($record);
            $isCreatingOrRenaming = $record->getRawOriginal('name') !== $optionName;
            $record->fill(['name' => $optionName, 'value' => $value]);
        } else {
            $isCreatingOrRenaming = true;
            $record = $this->optionQuery()->newModelInstance([
                'name' => $optionName,
                'value' => $value,
            ]);

            if (config('aura.teams')) {
                $record->setAttribute('team_id', $this->current_team_id);
            }
        }

        $record->setAttribute('owner_identity', $this->optionOwnerIdentity());

        try {
            if ($record->trashed()) {
                $this->requireSuccessfulOptionMutation($record->restore());
            } else {
                $this->requireSuccessfulOptionMutation($record->save());
            }
        } catch (UniqueConstraintViolationException $exception) {
            if (! $isCreatingOrRenaming) {
                throw $exception;
            }

            $conflict = $this->optionQuery()
                ->withTrashed()
                ->where('name', $optionName)
                ->lockForUpdate()
                ->first();

            if (! $conflict instanceof Option) {
                throw $exception;
            }

            $record = $this->verifiedOptionRecord($conflict);
            $record->fill(['value' => $value]);
            $record->setAttribute('owner_identity', $this->optionOwnerIdentity());

            if ($record->trashed()) {
                $this->requireSuccessfulOptionMutation($record->restore());
            } else {
                $this->requireSuccessfulOptionMutation($record->save());
            }
        }

        $aliases = $this->optionQuery()
            ->withTrashed()
            ->whereIn('name', array_slice($this->optionNames($option), 1))
            ->where($record->getKeyName(), '!=', $record->getKey())
            ->lockForUpdate()
            ->get();

        $aliases = $aliases->map(fn (Option $alias): Option => $this->verifiedOptionRecord($alias));

        foreach ($aliases as $alias) {
            $this->requireSuccessfulOptionMutation($alias->forceDelete());
        }

        return $record;
    }

    protected function readsLegacyOptionNames(): bool
    {
        return $this->getKeyType() === 'int';
    }

    /**
     * Cache a scalar snapshot and restore the configured Team models only at
     * the public boundary. This remains safe when object unserialization is
     * disabled by the cache store.
     */
    protected function rememberTeams(string $namespace, callable $resolver): Collection
    {
        $teamClass = config('aura.resources.team');
        $teamPrototype = new $teamClass;
        $teamPrototype->setConnection($this->getConnectionName());
        $payload = VersionedCache::remember(
            $namespace,
            'teams',
            now()->addHour(),
            function () use ($resolver): array {
                return ['teams' => $this->serializeTeams($resolver())];
            },
            $teamPrototype->getConnection(),
        );

        $metaPrototype = new Meta;
        $teamsRelation = null;

        $teams = collect($payload['teams'])->map(function (array $snapshot) use ($teamPrototype, $metaPrototype, &$teamsRelation) {
            $team = $teamPrototype->newFromBuilder(
                $snapshot['attributes'],
                $snapshot['connection'] ?? null,
            );

            $meta = collect($snapshot['meta'] ?? [])->map(fn (array $attributes) => $metaPrototype->newFromBuilder(
                $attributes,
                $team->getConnectionName(),
            ));

            $team->setRelation('meta', $metaPrototype->newCollection($meta->all()));

            if (is_array($snapshot['pivot'] ?? null)) {
                $teamsRelation ??= $this->teams();
                $team->setRelation('pivot', $teamsRelation->newExistingPivot($snapshot['pivot']));
            }

            return $team;
        });

        return $teamPrototype->newCollection($teams->all());
    }

    protected function requireSuccessfulOptionMutation(?bool $succeeded): void
    {
        if ($succeeded !== true) {
            throw new RuntimeException('Option persistence was vetoed.');
        }
    }

    /**
     * @return array{found: bool, value: mixed}
     */
    protected function resolveOptionEntry(string $option): array
    {
        $payload = VersionedCache::remember(
            $this->optionCacheNamespace(),
            $this->optionCacheVariant($option),
            now()->addHour(),
            function () use ($option): array {
                return $this->optionConnection()->transaction(function () use ($option): array {
                    $record = null;

                    foreach ($this->optionNames($option) as $name) {
                        $record = $this->activeOptionQuery()
                            ->where('name', $name)
                            ->lockForUpdate()
                            ->first();

                        if ($record !== null) {
                            $record = $this->verifiedOptionRecord($record);

                            break;
                        }
                    }

                    return [
                        'found' => $record !== null,
                        'value' => $record?->getAttributeValue('value'),
                    ];
                });
            },
            $this->optionConnection(),
        );

        return [
            'found' => $payload['found'],
            'value' => $payload['value'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveWildcardOptionValues(string $optionPrefix): array
    {
        $values = [];

        if ($this->readsLegacyOptionNames()) {
            $legacyPrefix = $this->legacyOptionName($optionPrefix);
            $values = $this->wildcardOptionValuesForPrefix($legacyPrefix);
        }

        $versionTwoPrefix = static::versionTwoOptionNamePrefixFor($this->getKey()).$optionPrefix;
        $values = array_replace($values, $this->wildcardOptionValuesForPrefix($versionTwoPrefix));
        $canonicalPrefix = $this->optionName($optionPrefix);

        return array_replace(
            $values,
            $this->wildcardOptionValuesForPrefix($canonicalPrefix),
        );
    }

    /**
     * @return array<int, array{attributes: array<string, mixed>, connection: string|null, meta: array<int, array<string, mixed>>, pivot: array<string, mixed>|null}>
     */
    protected function serializeTeams(Collection $teams): array
    {
        return $teams->map(fn ($team): array => [
            'attributes' => $team->getAttributes(),
            'connection' => $team->getConnectionName(),
            'meta' => $team->meta->map(fn (Meta $meta): array => $meta->getAttributes())->all(),
            'pivot' => $team->relationLoaded('pivot') ? $team->pivot->getAttributes() : null,
        ])->all();
    }

    protected static function teamsCacheNamespace(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        return 'teams.user.'.static::connectionCacheIdentity($connection).'.'.$userId;
    }

    protected static function unscopedLegacyOptionCacheNamespaceFor(
        string|int $userId,
        string|int $teamId,
    ): string {
        return 'option.user.'.$userId.'.team.'.$teamId;
    }

    protected function verifiedOptionRecord(Option $record): Option
    {
        if (! $this->optionConnection()->getSchemaBuilder()->hasColumn($record->getTable(), 'owner_identity')) {
            return $record;
        }

        $ownerIdentity = $record->getRawOriginal('owner_identity');

        if ($ownerIdentity !== null) {
            $this->assertOptionOwnerIdentity($ownerIdentity, $record->getKey());

            return $record;
        }

        $name = (string) $record->getRawOriginal('name');
        $isVerifiableAlias = str_starts_with(
            $name,
            static::versionTwoOptionNamePrefixFor($this->getKey()),
        ) || ($this->readsLegacyOptionNames() && str_starts_with($name, $this->legacyOptionName('')));

        if (! $isVerifiableAlias) {
            throw OptionOwnerIdentityException::forOption($record->getKey());
        }

        $this->optionQuery()
            ->withTrashed()
            ->whereKey($record->getKey())
            ->whereNull('owner_identity')
            ->update(['owner_identity' => $this->optionOwnerIdentity()]);

        $record = $this->optionQuery()
            ->withTrashed()
            ->whereKey($record->getKey())
            ->first();

        if (! $record instanceof Option) {
            throw OptionOwnerIdentityException::forOption(null);
        }

        $this->assertOptionOwnerIdentity($record->getRawOriginal('owner_identity'), $record->getKey());

        return $record;
    }

    protected static function versionTwoOptionNamePrefixFor(string|int $userId): string
    {
        return 'aura-user-option-v2:'.VersionedCache::identity('option.user.owner', $userId).':';
    }

    /**
     * @return array<string|int, mixed>
     */
    protected function wildcardOptionValuesForPrefix(string $physicalPrefix): array
    {
        return $this->activeOptionQuery()
            ->where('name', 'like', $physicalPrefix.'%')
            ->lockForUpdate()
            ->get()
            ->mapWithKeys(function (Option $record) use ($physicalPrefix): array {
                $record = $this->verifiedOptionRecord($record);
                $name = (string) $record->getRawOriginal('name');

                return [
                    substr($name, strlen($physicalPrefix)) => $record->getAttributeValue('value'),
                ];
            })
            ->all();
    }

    private static function currentTeamCacheEpochKey(
        string|int $userId,
        ?Connection $connection = null,
    ): string {
        return static::connectionScopedCacheKey(
            "current_team_generation_user_{$userId}",
            $connection,
        );
    }

    private static function ensureCurrentTeamCacheStoreIsCoherent(): void
    {
        if (Cache::getStore() instanceof FailoverStore) {
            throw new \LogicException(
                'Failover cache stores are not supported for current-team cache epochs.',
            );
        }
    }

    private function isTeamOnOwnConnection(mixed $team): bool
    {
        $teamClass = config('aura.resources.team', Team::class);

        return $team instanceof $teamClass
            && $team->exists
            && static::connectionCacheIdentity($team->getConnection())
                === static::connectionCacheIdentity($this->getConnection());
    }
}
