<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public static function getWidgets(): array
    {
        return [];
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

        // Any catalog write (including creating or deleting a Shadow) bumps the
        // Role Catalog version so every user's resolved-roles memo recomputes on
        // its next permission check — instant shadow effect, no per-call queries.
        static::saved(fn () => static::bumpCatalogVersion());
        static::deleted(fn () => static::bumpCatalogVersion());
    }
}
