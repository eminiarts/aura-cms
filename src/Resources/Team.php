<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\TeamFactory;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\TeamUser;
use Aura\Base\Resource;
use Aura\Base\Services\VersionedCache;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Team extends Resource
{
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
        VersionedCache::bump(self::optionCacheNamespaceFor($teamId), $connection);
        VersionedCache::bump(self::legacyOptionCacheNamespaceFor($teamId), $connection);
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
        $optionName = $this->optionName($option);

        Option::withoutGlobalScope(TeamScope::class)
            ->where('team_id', $this->id)
            ->where('name', $optionName)
            ->delete();

        $this->forgetOptionCache($option);
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
                    'values' => Option::withoutGlobalScope(TeamScope::class)
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
        $optionName = $this->optionName($option);

        return VersionedCache::remember(
            $this->optionCacheNamespace(),
            $optionName,
            now()->addHour(),
            function () use ($optionName): array {
                $record = Option::withoutGlobalScope(TeamScope::class)
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
        $optionName = $this->optionName($option);
        $attributes = ['name' => $optionName, 'team_id' => $this->id];
        $record = Option::withoutGlobalScope(TeamScope::class)
            ->withTrashed()
            ->where($attributes)
            ->first();

        if ($record) {
            $record->fill(['value' => $value]);

            if ($record->trashed()) {
                $record->restore();
            } else {
                $record->save();
            }
        } else {
            $record = Option::withoutGlobalScope(TeamScope::class)->updateOrCreate(
                $attributes,
                ['value' => $value],
            );
        }

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
        return $this->belongsToMany(User::class, 'user_role')
            ->using(TeamUser::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    protected static function booted()
    {
        parent::booted();

        // Team writes can change both member snapshots and the shared Global
        // Admin switcher, including rename and restore operations.
        static::saved(function ($team) {
            $connection = $team->getConnection();

            $connection->table('user_role')
                ->where('team_id', $team->id)
                ->pluck('user_id')
                ->unique()
                ->each(fn ($userId) => User::clearTeamsCache($userId, $connection));

            User::clearGlobalAdminTeamsCache($connection);
        });

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

        static::creating(function ($team) {});

        static::created(function ($team) {

            if ($user = auth()->user()) {
                // Change the current team id of the user
                // $user->switchTeam($team);

                $user->current_team_id = $team->id;
                $user->save();
            }

            // Attach-don't-mint: creating a team no longer mints a per-team admin
            // role. The creator is attached to the shared global `admin` Global
            // Role (team_id = null, super_admin), with the Membership recording
            // the team. The helper self-heals the Global Role when the catalog
            // was never seeded (bare `migrate`, or the test harness).
            $globalAdmin = Role::firstOrCreateGlobalAdmin();

            // Attach the current user to the team via the global admin role.
            if ($user) {
                $team->users()->attach($user->id, ['role_id' => $globalAdmin->id]);
            }

            // Create all permissions for the team
            GenerateAllResourcePermissions::dispatch($team->id);
        });

        static::deleted(function ($team) {
            $connection = $team->getConnection();
            $affectedMemberIds = $connection->table('user_role')
                ->where('team_id', $team->id)
                ->pluck('user_id');

            $optionNames = Option::withoutGlobalScopes()
                ->where('team_id', $team->id)
                ->pluck('name');

            $userClass = config('aura.resources.user', User::class);
            $userModel = new $userClass;
            $optionUserIds = $userClass::withoutGlobalScopes()
                ->pluck($userModel->getKeyName())
                ->filter(fn (string|int $userId): bool => $optionNames->contains(
                    fn (string $name): bool => $userClass::optionNameBelongsToUser($name, $userId)
                ));

            // Get all users who had the deleted team as their current team
            $users = User::withoutGlobalScopes()
                ->where('current_team_id', $team->id)
                ->get();
            $reassignedUserIds = $users->pluck('id');

            // Loop through the users and update their current_team_id
            foreach ($users as $user) {
                $firstTeam = $user->teams()
                    ->withoutGlobalScopes()
                    ->where('teams.id', '!=', $team->id)
                    ->first();
                $user->current_team_id = $firstTeam ? $firstTeam->id : null;
                $user->save();

                User::clearCurrentTeamCache($user->id);
            }

            // A team's Memberships and its own Team Roles (including Shadows) die
            // with the team; the shared Global Roles (team_id = null) are never
            // touched. Remove the pivot rows first, then the team-owned roles.
            $connection->table('user_role')->where('team_id', $team->id)->delete();

            // Bypass TeamScope: by this point the affected users' current team has
            // already been reassigned above, so a scoped query would filter to the
            // wrong team and delete nothing.
            Role::withoutGlobalScopes()->where('team_id', $team->id)->delete();

            // The role rows above were removed via a mass delete (no model
            // events), so bump the catalog version explicitly to invalidate every
            // user's resolved-roles memo.
            Role::bumpCatalogVersion($connection);

            // Delete all the team's metas
            $team->meta()->delete();

            // Delete all the team's invitations
            $team->teamInvitations()->delete();

            // Delete every option physically owned by the team. Names can be
            // team.*, user.*, or application-defined, so team_id is the only
            // complete ownership boundary and the query must bypass TeamScope.
            Option::withoutGlobalScopes()->where('team_id', $team->id)->forceDelete();

            static::clearOptionCacheForTeam($team->id, $connection);
            User::clearOptionCacheForScope($team->id, $connection);

            $affectedMemberIds
                ->merge($reassignedUserIds)
                ->merge($optionUserIds)
                ->unique()
                ->each(fn ($userId) => User::clearLegacyOptionCacheForTeam($userId, $team->id, $connection));

            $reassignedUserIds->each(function ($userId) {
                User::clearCurrentTeamCache($userId);
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

        VersionedCache::bump($this->optionCacheNamespace(), $connection);
        VersionedCache::bump($this->legacyOptionCacheNamespace(), $connection);
    }

    protected function legacyOptionCacheNamespace(): string
    {
        return self::legacyOptionCacheNamespaceFor($this->id);
    }

    protected static function legacyOptionCacheNamespaceFor(string|int $teamId): string
    {
        return 'option.team.'.$teamId;
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

    protected function optionCacheNamespace(): string
    {
        return self::optionCacheNamespaceFor($this->id);
    }

    protected static function optionCacheNamespaceFor(string|int $teamId): string
    {
        return 'option.team.v2.'.VersionedCache::identity('option.team.scope', $teamId);
    }

    protected function optionConnection(): Connection
    {
        return (new Option)->getConnection();
    }

    protected function optionName(string $option): string
    {
        return 'team.'.$this->id.'.'.$option;
    }
}
