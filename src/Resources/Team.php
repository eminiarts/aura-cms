<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\TeamFactory;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\TeamUser;
use Aura\Base\Resource;
use Aura\Base\Services\VersionedCache;
use Aura\Base\Traits\HasTeamMemberships;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use RuntimeException;

class Team extends Resource
{
    use HasTeamMemberships;
    use SoftDeletes;

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'team';

    public static string $type = 'Team';

    public static bool $usesMeta = true;

    protected $fillable = [
        'name', 'user_id', 'fields',
    ];

    protected static ?string $group = 'Global';

    protected $table = 'teams';

    protected static bool $title = false;

    public function actions()
    {
        return [
            'deleteAction' => [
                'label' => 'Delete',
                'ability' => 'delete',
                'icon-view' => 'aura::components.actions.trash',
                'class' => 'hover:text-red-700 text-red-500 font-bold',
                'conditional_logic' => function () {
                    return auth()->user()->isAuraGlobalAdmin();
                },
            ],
        ];
    }

    public function clearCachedOption($option)
    {
        $this->forgetOptionCache($option);
    }

    public static function clearOptionCacheForTeam(string|int $teamId, ?Connection $connection = null): void
    {
        VersionedCache::bump(self::optionCacheNamespaceFor($teamId, $connection), $connection);
        VersionedCache::bump(self::legacyOptionCacheNamespaceFor($teamId, $connection), $connection);
        VersionedCache::bump(self::unscopedLegacyOptionCacheNamespaceFor($teamId), $connection);
    }

    public function customPermissions()
    {
        return [
            'invite-users' => 'Invite users to team',
        ];
    }

    public function deleteAction()
    {
        if (! auth()->user()->can('delete', $this)) {
            abort(403, 'You are not authorized to delete this team.');
        }

        $this->delete();

        return redirect()->to($this->indexUrl());
    }

    public function deleteOption($option)
    {
        $this->assertNotReservedPreferenceOwner();
        $optionName = $this->optionName($option);
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($optionName): void {
            $record = $this->activeOptionQuery()
                ->where('team_id', $this->id)
                ->where('name', $optionName)
                ->lockForUpdate()
                ->first();

            if ($record !== null) {
                $this->requireSuccessfulOptionMutation($record->delete());
            }
        });

        $this->forgetOptionCache($option, $connection);
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
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Users',
                'slug' => 'tab-users',
                'global' => true,
                'on_create' => false,
            ],
            [
                'name' => 'Users',
                'slug' => 'users',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\User',
                'validation' => '',
                'foreign_key' => 'team_id',
                'conditional_logic' => [],
                'relation' => function ($query, $model) {
                    return $query;
                },
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
                'slug' => 'tab-invitations',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'global' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_create' => false,
            ],
            [
                'name' => 'Invitations',
                'slug' => 'invitations',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\TeamInvitation',
                'validation' => '',
                'conditional_logic' => [],
                'relation' => function ($query, $model) {
                    return $query->withoutGlobalScopes()->where('team_id', $model->id);
                },
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
        return view('aura::components.icon.team')->render();
    }

    public function getOption($option)
    {
        $this->assertNotReservedPreferenceOwner();

        if (! $this->hasAuthorizedOptionContext()) {
            return str_ends_with((string) $option, '*') ? collect() : null;
        }

        $optionName = $this->optionName($option);

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($optionName, -1) == '*') {

            $wildcardPrefix = substr($optionName, 0, -1);

            $payload = VersionedCache::remember(
                $this->optionCacheNamespace(),
                $optionName,
                now()->addHour(),
                fn (): array => [
                    'values' => $this->activeOptionQuery()
                        ->where('team_id', $this->id)
                        ->where('name', 'like', $wildcardPrefix.'%')
                        ->orderBy('id')
                        ->get(['name', 'value'])
                        ->mapWithKeys(function (Option $record): array {
                            $name = (string) $record->getRawOriginal('name');

                            return [
                                str($name)->afterLast('.')->toString() => $record->getAttributeValue('value'),
                            ];
                        })
                        ->all(),
                ],
                $this->optionConnection(),
            );

            return collect($payload['values']);
        }

        return $this->getOptionEntry($option)['value'];
    }

    /**
     * @return array{found: bool, value: mixed}
     */
    public function getOptionEntry($option): array
    {
        $this->assertNotReservedPreferenceOwner();

        if (! $this->hasAuthorizedOptionContext()) {
            return ['found' => false, 'value' => null];
        }

        return $this->getOptionEntryExplicit((string) $option);
    }

    /**
     * Read an option for this explicit team without consulting auth().
     *
     * @return array{found: bool, value: mixed}
     */
    public function getOptionEntryExplicit(string $option): array
    {
        $this->assertNotReservedPreferenceOwner();
        $optionName = $this->optionName($option);

        return VersionedCache::remember(
            $this->optionCacheNamespace(),
            $optionName,
            now()->addHour(),
            function () use ($optionName): array {
                $record = $this->activeOptionQuery()
                    ->where('team_id', $this->id)
                    ->where('name', $optionName)
                    ->first(['value']);

                return [
                    'found' => $record !== null,
                    'value' => $record?->getAttributeValue('value'),
                ];
            },
            $this->optionConnection(),
        );
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
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
        $this->assertNotReservedPreferenceOwner();
        $optionName = $this->optionName($option);
        $attributes = ['name' => $optionName, 'team_id' => $this->id];
        $connection = $this->optionConnection();

        $record = $connection->transaction(function () use ($attributes, $connection, $value): Option {
            $record = Option::on($this->getConnectionName())
                ->withoutGlobalScope(TeamScope::class)
                ->withTrashed()
                ->where($attributes)
                ->lockForUpdate()
                ->first();
            $isCreating = $record === null;

            if ($record !== null) {
                $record->fill(['value' => $value]);
            }

            try {
                if ($isCreating) {
                    $record = Option::createForTeamForSystem(
                        $this->getKey(),
                        [...$attributes, 'user_id' => null, 'value' => $value],
                        $connection,
                    );
                } else {
                    $this->persistOptionRecord($record);
                }
            } catch (UniqueConstraintViolationException $exception) {
                if (! $isCreating) {
                    throw $exception;
                }

                $record = Option::on($this->getConnectionName())
                    ->withoutGlobalScope(TeamScope::class)
                    ->withTrashed()
                    ->where($attributes)
                    ->lockForUpdate()
                    ->first();

                if (! $record instanceof Option) {
                    throw $exception;
                }

                $record->fill(['value' => $value]);
                $this->persistOptionRecord($record);
            }

            return $record;
        });

        $this->forgetOptionCache($option, $record->getConnection());
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

    public function users(): BelongsToMany
    {
        return $this->teamMembershipsToMany(User::class, 'team_id', 'user_id', 'users')
            ->using(TeamUser::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    protected function activeOptionQuery(): Builder
    {
        $option = new Option;
        $query = Option::on($this->getConnectionName())->withoutGlobalScopes();

        if ($this->optionConnection()->getSchemaBuilder()->hasColumn($option->getTable(), $option->getDeletedAtColumn())) {
            $query->whereNull($option->getQualifiedDeletedAtColumn());
        }

        return $query;
    }

    protected function assertNotReservedPreferenceOwner(): void
    {
        $teamId = $this->getAttributes()[$this->getKeyName()] ?? $this->getKey();

        if (Option::isEveryoneTeamId($teamId)) {
            throw new InvalidArgumentException('Team ID 0 is reserved for everyone preferences.');
        }
    }

    protected static function booted()
    {
        parent::booted();

        // Team writes can change both member snapshots and the shared Global
        // Admin switcher, including rename and restore operations.
        static::saved(function ($team) {
            $connection = $team->getConnection();

            $connection->table('user_role')
                ->useWritePdo()
                ->where('team_id', $team->id)
                ->pluck('user_id')
                ->unique()
                ->each(fn ($userId) => User::clearTeamsCache($userId, $connection));

            User::clearGlobalAdminTeamsCache($connection);
        });

        static::saving(function ($team) {
            $team->assertNotReservedPreferenceOwner();

            // unset title attribute
            unset($team->title);
            unset($team->content);
            unset($team->type);
            unset($team->team_id);

            $authenticatedUser = auth()->user();

            if ($authenticatedUser !== null && ! $authenticatedUser instanceof User) {
                throw new \LogicException('Only authenticated Aura users may create or update teams.');
            }

            $authenticatedUserId = $authenticatedUser instanceof User
                ? $authenticatedUser->getKey()
                : null;

            if (($team->user_id === null || $team->user_id === '')
                && $authenticatedUserId !== null
                && self::authenticatedUserUsesConnection($authenticatedUser, $team->getConnection())
            ) {
                $team->user_id = $authenticatedUserId;
            }
        });

        static::creating(function ($team) {});

        static::deleting(fn (Team $team) => $team->assertNotReservedPreferenceOwner());

        static::created(function ($team) {
            $connection = $team->getConnection();
            $connectionName = $connection->getName();
            $authenticatedUser = auth()->user();
            $authenticatedUserMatchesConnection = self::authenticatedUserUsesConnection(
                $authenticatedUser,
                $connection,
            );

            $user = $authenticatedUserMatchesConnection ? $authenticatedUser : null;

            if ($user) {
                // Change the current team id of the user
                // $user->switchTeam($team);

                $user->setAttribute('current_team_id', $team->id);
                $user->save();
            }

            // Attach-don't-mint: creating a team no longer mints a per-team admin
            // role. The creator is attached to the shared global `admin` Global
            // Role (team_id = null, super_admin), with the Membership recording
            // the team. The helper self-heals the Global Role when the catalog
            // was never seeded (bare `migrate`, or the test harness).
            $globalAdmin = Role::firstOrCreateGlobalAdmin($connection);

            // Attach the current user to the team via the global admin role.
            if ($user) {
                $team->users()->attach($user->getKey(), ['role_id' => $globalAdmin->id]);
                User::clearTeamsCache($user->getKey(), $connection);
            }

            // A Global Admin's switcher lists every team from one shared cache
            // key — invalidate it so a newly created team appears immediately.
            User::clearGlobalAdminTeamsCache($connection);
            // Create all permissions for the team
            GenerateAllResourcePermissions::dispatch($team->id, $connectionName);
        });

        static::deleted(function ($team) {
            $connection = $team->getConnection();
            $connectionName = $connection->getName();
            $teamId = $team->getKey();
            $authenticatedUser = User::authenticatedResource();

            if ($authenticatedUser instanceof User
                && User::connectionCacheIdentity($authenticatedUser->getConnection()) === User::connectionCacheIdentity($connection)) {
                $authenticatedCurrentTeamId = $authenticatedUser->getAttribute('current_team_id');

                VersionedCache::afterRollback(
                    $connection,
                    function () use ($authenticatedCurrentTeamId, $authenticatedUser): void {
                        $authenticatedUser->forceFill(['current_team_id' => $authenticatedCurrentTeamId]);
                        $authenticatedUser->unsetRelation('currentTeam');
                        $authenticatedUser->unsetRelation('teams');
                    },
                );
            }

            $affectedMemberIds = $connection->table('user_role')
                ->useWritePdo()
                ->where('team_id', $teamId)
                ->pluck('user_id');

            $optionTableExists = $connection->getSchemaBuilder()->hasTable((new Option)->getTable());
            $optionNames = $optionTableExists
                ? Option::on($connectionName)
                    ->withoutGlobalScopes()
                    ->where('team_id', $teamId)
                    ->pluck('name')
                : collect();

            $userClass = config('aura.resources.user', User::class);
            $userModel = new $userClass;
            $userModel->setConnection($connectionName);
            $optionUserIds = $userModel->newQueryWithoutScopes()
                ->pluck($userModel->getKeyName())
                ->filter(fn (string|int $userId): bool => $optionNames->contains(
                    fn (string $name): bool => $userClass::optionNameBelongsToUser($name, $userId)
                ));

            // Get all users who had the deleted team as their current team
            $users = User::on($connectionName)
                ->withoutGlobalScopes()
                ->without('meta')
                ->useWritePdo()
                ->where('current_team_id', $teamId)
                ->get();
            $reassignedUserIds = $users->pluck('id');

            // Loop through the users and update their current_team_id
            foreach ($users as $user) {
                $firstTeam = $user->teams()
                    ->withoutGlobalScope(TeamScope::class)
                    ->without('meta')
                    ->useWritePdo()
                    ->where('teams.id', '!=', $teamId)
                    ->first();
                $currentTeamId = $firstTeam?->getKey();

                $user->forceFill(['current_team_id' => $currentTeamId])->saveQuietly();

                User::clearCurrentTeamCache($user->getKey(), $connection);
            }

            // A team's Memberships and its own Team Roles (including Shadows) die
            // with the team; the shared Global Roles (team_id = null) are never
            // touched. Remove the pivot rows first, then the team-owned roles.
            $connection->table('user_role')->where('team_id', $teamId)->delete();

            // Bypass TeamScope: by this point the affected users' current team has
            // already been reassigned above, so a scoped query would filter to the
            // wrong team and delete nothing.
            Role::on($connectionName)
                ->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->delete();

            // The role rows above were removed via a mass delete (no model
            // events), so bump the catalog version explicitly to invalidate every
            // user's resolved-roles memo.
            Role::bumpCatalogVersion($connection);

            // Delete all the team's metas
            $team->meta()->delete();

            // Delete all the team's invitations
            $team->teamInvitations()->withoutGlobalScope(TeamScope::class)->delete();

            // Delete every option physically owned by the team. Names can be
            // team.*, user.*, or application-defined, so team_id is the only
            // complete ownership boundary and the query must bypass TeamScope.
            if ($optionTableExists) {
                Option::on($connectionName)
                    ->withoutGlobalScopes()
                    ->where('team_id', $teamId)
                    ->forceDelete();
            }

            static::clearOptionCacheForTeam($team->id, $connection);
            User::clearOptionCacheForScope($team->id, $connection);

            $affectedMemberIds
                ->merge($reassignedUserIds)
                ->merge($optionUserIds)
                ->unique()
                ->each(fn ($userId) => User::clearLegacyOptionCacheForTeam($userId, $teamId, $connection));

            $reassignedUserIds->each(function ($userId) use ($connection) {
                User::clearCurrentTeamCache($userId, $connection);
            });

            $affectedMemberIds
                ->merge($reassignedUserIds)
                ->unique()
                ->each(function ($userId) use ($connection) {
                    User::clearTeamsCache($userId, $connection);
                });

            // Drop the shared Global Admin switcher cache so the deleted team
            // no longer shows up for Global Admins.
            User::clearGlobalAdminTeamsCache($connection);
        });

    }

    protected function forgetOptionCache(string $option, ?Connection $connection = null): void
    {
        $connection ??= $this->optionConnection();

        VersionedCache::bump($this->optionCacheNamespace($connection), $connection);
        VersionedCache::bump($this->legacyOptionCacheNamespace($connection), $connection);
        VersionedCache::bump(self::unscopedLegacyOptionCacheNamespaceFor($this->id), $connection);
    }

    protected function hasAuthorizedOptionContext(): bool
    {
        $team = User::authenticatedResource()?->authorizedCurrentTeam();

        return $team?->is($this) ?? false;
    }

    protected function legacyOptionCacheNamespace(?Connection $connection = null): string
    {
        return self::legacyOptionCacheNamespaceFor($this->id, $connection ?? $this->optionConnection());
    }

    protected static function legacyOptionCacheNamespaceFor(
        string|int $teamId,
        ?Connection $connection = null,
    ): string {
        return 'option.team.'.User::connectionCacheIdentity($connection).'.'.$teamId;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return TeamFactory::new();
    }

    protected function optionCacheNamespace(?Connection $connection = null): string
    {
        return self::optionCacheNamespaceFor($this->id, $connection ?? $this->optionConnection());
    }

    protected static function optionCacheNamespaceFor(
        string|int $teamId,
        ?Connection $connection = null,
    ): string {
        return 'option.team.v2.'.VersionedCache::identity(
            'option.team.scope',
            User::connectionCacheIdentity($connection),
            $teamId,
        );
    }

    protected function optionConnection(): Connection
    {
        return $this->getConnection();
    }

    protected function optionName(string $option): string
    {
        return 'team.'.$this->id.'.'.$option;
    }

    protected function persistOptionRecord(Option $record): void
    {
        if ($record->trashed()) {
            $this->requireSuccessfulOptionMutation($record->restore());

            return;
        }

        $this->requireSuccessfulOptionMutation($record->save());
    }

    protected function requireSuccessfulOptionMutation(?bool $succeeded): void
    {
        if ($succeeded !== true) {
            throw new RuntimeException('Option persistence was vetoed.');
        }
    }

    protected static function unscopedLegacyOptionCacheNamespaceFor(string|int $teamId): string
    {
        return 'option.team.'.$teamId;
    }

    private static function authenticatedUserUsesConnection(mixed $authenticatedUser, Connection $connection): bool
    {
        return $authenticatedUser instanceof User
            && User::connectionCacheIdentity($authenticatedUser->getConnection())
                === User::connectionCacheIdentity($connection);
    }
}
