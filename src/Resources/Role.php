<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Gate;

class Role extends Resource
{
    public array $actions = [
        'createMissingPermissions' => [
            'label' => 'Create Missing Permissions',
            'description' => 'Create missing permissions if you have added new resources.',
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
        'delete' => [
            'label' => 'Delete',
            'icon' => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $customTable = true;

    /**
     * Transient "make this role global" intent captured from the guarded
     * `is_global` form toggle. It is NOT a database column: the setIsGlobal
     * mutator (setIsGlobalAttribute) diverts the submitted value here so it
     * never reaches an INSERT,
     * and the `saving` hook below translates it into the authoritative team_id
     * write (team_id = null for a Global Role), gated on the actor being a
     * Global Admin. `null` means the toggle was never submitted (leave team_id
     * exactly as the normal pipeline set it).
     */
    public ?bool $globalIntent = null;

    public static $globalSearch = false;

    public static ?string $slug = 'role';

    public static ?int $sort = 2;

    public static string $type = 'Role';

    public static bool $usesMeta = false;

    protected $casts = [
        'permissions' => 'array',
        'super_admin' => 'boolean',
    ];

    /**
     * Monotonic Role Catalog version, bumped whenever any role is written or
     * deleted (including quiet catalog-role writes). It keys the per-instance
     * resolved-roles memo (User::cachedRoles) so creating or deleting a Shadow
     * invalidates every user's cache lazily — instant shadow effect without
     * paying resolution queries on every permission check.
     */
    protected static int $catalogVersion = 0;

    protected static $dropdown = 'Users';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'super_admin',
        'permissions',
        'team_id',
        'is_global',
    ];

    protected static ?string $group = 'Aura';

    protected $table = 'roles';

    protected $with = [];

    /**
     * Bump the Role Catalog version, invalidating every user's resolved-roles
     * memo. Called from Role write/delete hooks and from the quiet catalog-role
     * writes (which fire no model events) so a Shadow or catalog role created or
     * deleted mid-request takes effect on the next permission check.
     */
    public static function bumpCatalogVersion(): void
    {
        static::$catalogVersion++;
    }

    /**
     * The default attributes for a base Role Catalog role, keyed by slug.
     *
     * Single source of truth for the seeded/self-healed catalog defaults, shared
     * by RoleCatalogSeeder, firstOrCreateCatalogRole(), the MakeUser command and
     * the test helpers. (The upgrade migration deliberately keeps its own copy —
     * a migration must not depend on model code.)
     *
     * @return array{name: string, slug: string, description: string, super_admin: bool, permissions: array<string, bool>}
     */
    public static function catalogDefaults(string $slug): array
    {
        return match ($slug) {
            'admin' => [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Admin can perform everything.',
                'super_admin' => true,
                'permissions' => [],
            ],
            'user' => [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Default role with minimal permissions.',
                'super_admin' => false,
                'permissions' => [],
            ],
            default => throw new \InvalidArgumentException("Unknown catalog role slug [{$slug}]."),
        };
    }

    /**
     * The monotonic Role Catalog version. Keys the per-instance resolved-roles
     * memo (User::cachedRoles) so creating or deleting a catalog role or Shadow
     * invalidates every user's cache lazily.
     */
    public static function catalogVersion(): int
    {
        return static::$catalogVersion;
    }

    public function createMissingPermissions()
    {
        GenerateAllResourcePermissions::dispatch();
    }

    /**
     * Ensure a base Role Catalog role (Global Role, team_id = null) exists and
     * return it. Self-heals installs whose catalog was never seeded (a bare
     * `migrate`, or the test harness, which does not run aura:install), using the
     * shared catalogDefaults().
     *
     * The row is written with saveQuietly() so the InitialPostFields saving hook
     * — which auto-assigns the current team's id whenever team_id is unset — does
     * not silently re-team the Global Role. In Teams-off mode the roles table has
     * no team_id column, so the flat catalog row is used as-is. The catalog
     * version is bumped explicitly since quiet writes fire no model events.
     */
    public static function firstOrCreateCatalogRole(string $slug): self
    {
        $query = static::withoutGlobalScopes()->where('slug', $slug);

        if (config('aura.teams')) {
            $query->whereNull('team_id');
        }

        if ($role = $query->first()) {
            return $role;
        }

        $attributes = static::catalogDefaults($slug);

        if (config('aura.teams')) {
            // team_id is fillable, so passing it explicitly writes a Global Role.
            $attributes['team_id'] = null;
        }

        $role = static::withoutGlobalScopes()->newModelInstance($attributes);
        $role->saveQuietly();

        static::bumpCatalogVersion();

        return $role;
    }

    /**
     * Ensure the shared global `admin` Global Role (Super Admin) exists. Backs
     * the "attach-don't-mint" model: team creation and registration attach the
     * creator to this single role instead of minting a per-team admin row.
     */
    public static function firstOrCreateGlobalAdmin(): self
    {
        return static::firstOrCreateCatalogRole('admin');
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                // Mark Global Roles (team_id = null) in the merged team-context
                // index so a Team Super Admin sees at a glance which rows are
                // the shared, read-only catalog roles. Teams-off has no team_id
                // column, so the badge blade renders nothing there.
                'display_view' => 'aura::components.fields.role-name-index',
                'searchable' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'based_on' => 'name',
                'custom' => false,
                'disabled' => true,
                'validation' => 'required',
                'on_index' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'super_admin',
                'type' => 'Aura\Base\Fields\Boolean',
                'instructions' => 'Admins have access to all permissions and can manage other users.',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'live' => true,
                'default' => false,
            ],
            [
                'name' => 'Global Role',
                'slug' => 'is_global',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Available in every team as part of the shared Role Catalog. Only a Global Admin can define global roles.',
                'validation' => '',
                'default' => false,
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                // Client-advisory only: the toggle is shown to Global Admins so
                // they can promote a role to the catalog. The authoritative
                // guard lives server-side in the `saving` hook, which ignores
                // the intent for any non-Global-Admin actor. Teams-off has no
                // global/team distinction, so the toggle is hidden entirely.
                'conditional_logic' => function ($model, $post) {
                    return config('aura.teams') && auth()->check() && Gate::allows('AuraGlobalAdmin');
                },
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Permissions',
                'on_index' => false,
                'type' => 'Aura\\Base\\Fields\\Permissions',
                'validation' => '',
                'conditional_logic' => function ($model, $form) {

                    if (optional(optional($form)['fields'])['super_admin']) {
                        return false;
                    }

                    return true;
                },
                'slug' => 'permissions',
                'resource' => 'Aura\\Base\\Resources\\Permission',
            ],
        ];
    }

    public function getIcon()
    {
        return view('aura::components.icon.role')->render();
    }

    /**
     * Derive the `is_global` toggle value from the authoritative signal: a
     * Global Role is one with no team (team_id = null). Teams-off has no
     * team/global distinction, so the toggle is always off there.
     */
    public function getIsGlobalAttribute(): bool
    {
        // Read team_id straight from the loaded attributes: a fresh (unsaved)
        // Role has no team, and a Role fetched without its team_id column must
        // not trip strict-mode missing-attribute access. Only a persisted row
        // whose team_id is explicitly null is a Global Role.
        if (! config('aura.teams') || ! $this->exists) {
            return false;
        }

        return array_key_exists('team_id', $this->attributes) && $this->attributes['team_id'] === null;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * Merge/de-duplicate the Roles index in a team context. TeamScope makes both
     * the team's own Team Roles and every Global Role visible, so a Global Role
     * that the team Shadows would otherwise appear twice. Resolve to the shown
     * set — each slug once, the Shadow winning — by dropping the Global rows a
     * Team Role of the same slug shadows for the current team. Applied as a
     * plain WHERE so it composes with search, sort and pagination.
     */
    public function indexQuery($query, $table = null)
    {
        if (config('aura.teams')) {
            $query->shadowResolved(optional(auth()->user())->current_team_id);
        }

        return $query;
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        return $this->hasMany(Meta::class, 'post_id');
    }

    /**
     * Resolve the effective role for a given slug within a team context.
     *
     * This is the single Role Catalog resolution seam. Shadowing is resolved by
     * slug at check time: when a team owns a role with the given slug (a Shadow),
     * that Team Role wins; otherwise the Global Role (team_id = null) applies.
     * In Teams-off mode there is only one scope, so the Global Role is the role.
     *
     * Membership pivot rows are never rewritten — creating or deleting a Shadow
     * changes the resolved role (and therefore permission outcomes) instantly.
     *
     * @param  string  $slug  The role slug that identifies the role within a team.
     * @param  int|null  $teamId  The team context to resolve within (null = global/Teams-off).
     */
    public static function resolveForTeam(string $slug, ?int $teamId = null): ?self
    {
        // Bypass TeamScope (and any other global scopes) so both the current
        // team's rows and the global (team_id = null) rows are considered.
        $base = static::withoutGlobalScopes();

        // Teams-off mode: the roles table has no team_id column, so there is a
        // single flat catalog. The global role simply is the role.
        if (! config('aura.teams')) {
            return (clone $base)->where('slug', $slug)->first();
        }

        // Teams-on: a Team Role (Shadow) wins over the Global Role by slug.
        if ($teamId !== null) {
            $teamRole = (clone $base)
                ->where('slug', $slug)
                ->where('team_id', $teamId)
                ->first();

            if ($teamRole) {
                return $teamRole;
            }
        }

        // Fall back to the Global Role definition.
        return (clone $base)
            ->where('slug', $slug)
            ->whereNull('team_id')
            ->first();
    }

    /**
     * Reduce the team-context role set to what the merged Roles index and the
     * role pickers show: each slug once, with a team's Shadow winning over the
     * Global Role it shadows. Concretely, exclude Global rows (team_id = null)
     * whose slug is also defined as a Team Role for the given team. Team Roles
     * and non-shadowed Global Roles are untouched. A no-op in Teams-off mode,
     * where the flat catalog already holds one row per slug.
     */
    public function scopeShadowResolved($query, ?int $teamId = null)
    {
        if (! config('aura.teams')) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        return $query->whereNot(function ($inner) use ($table, $teamId) {
            $inner->whereNull($table.'.team_id')
                ->whereExists(function ($sub) use ($table, $teamId) {
                    $sub->selectRaw('1')
                        ->from($table.' as shadow_roles')
                        ->whereColumn('shadow_roles.slug', $table.'.slug')
                        ->where('shadow_roles.team_id', $teamId);
                });
        });
    }

    /**
     * Constrain a role query to the roles visible within a team context: the
     * team's own Team Roles plus the shared Global Roles (team_id = null). This
     * is the single expression of the "team-or-global" shape used by the
     * assignable-roles guard, invitation acceptance and TeamScope's roles branch.
     */
    public function scopeVisibleToTeam($query, ?int $teamId)
    {
        $column = $query->getModel()->getTable().'.team_id';

        return $query->where(function ($query) use ($column, $teamId) {
            $query->where($column, $teamId)->orWhereNull($column);
        });
    }

    /**
     * Capture the guarded `is_global` toggle without ever letting it reach the
     * database as a column. The submitted value is diverted into the transient
     * $globalIntent property; the `saving` hook applies it (team_id = null) only
     * for a Global Admin actor. Non-global/absent submissions leave team_id as
     * the normal pipeline set it, so the toggle can never self-grant catalog
     * scope through mass assignment or form tampering.
     */
    public function setIsGlobalAttribute($value): void
    {
        $this->globalIntent = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_role')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function title()
    {
        if (isset($this->title)) {
            return $this->title." (#{$this->id})";
        } elseif (isset($this->name)) {
            return $this->name." (#{$this->id})";
        } else {
            return "Role (#{$this->id})";
        }
    }

    public function users(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->belongsToMany(User::class, 'user_role')
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps();
    }

    protected static function booted()
    {
        parent::booted();

        // Apply the guarded `is_global` toggle. Registered from booted() so it
        // runs AFTER InitialPostFields' saving hook (which auto-teams a new row
        // to the current team): only here can team_id be forced back to null for
        // a Global Admin promoting a role to the catalog. The intent is honored
        // for a Global Admin only; every other actor's toggle is silently
        // refused, so a Global Role can never be minted through form tampering.
        static::saving(function (self $role) {
            if ($role->globalIntent === null || ! config('aura.teams')) {
                return;
            }

            $actor = auth()->user();
            $isGlobalAdmin = $actor && Gate::forUser($actor)->allows(User::GLOBAL_ADMIN_GATE);

            if (! $isGlobalAdmin) {
                // Silent refusal: a non-Global-Admin can never produce a Global
                // Role. If nothing team-scoped it yet, pin it to the actor's
                // current team so the escalation attempt yields a Team Role.
                if ($role->getAttribute('team_id') === null) {
                    $role->setAttribute('team_id', optional($actor)->current_team_id);
                }

                return;
            }

            if ($role->globalIntent === true) {
                $role->setAttribute('team_id', null);
            } elseif ($role->getAttribute('team_id') === null) {
                // A Global Admin explicitly turning the toggle off demotes the
                // role to a Team Role in their current team.
                $role->setAttribute('team_id', optional($actor)->current_team_id);
            }
        });

        // Any catalog write (including creating or deleting a Shadow) bumps the
        // Role Catalog version so every user's resolved-roles memo recomputes on
        // its next permission check — instant shadow effect, no per-call queries.
        static::saved(fn () => static::bumpCatalogVersion());
        static::deleted(fn () => static::bumpCatalogVersion());
    }
}
