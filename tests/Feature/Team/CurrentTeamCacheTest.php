<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Roles as RolesField;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\UserTeams;
use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Providers\AuraEloquentUserProvider;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as FrameworkAuthenticatable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Livewire\livewire;

class AuthenticateQueueWorkerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(): void
    {
        Auth::setUser(User::withoutGlobalScopes()->findOrFail($this->userId));
    }
}

class ObserveGuestQueueWorkerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public static int|string|null $authenticatedUserId = null;

    public static ?int $visiblePostCount = null;

    public function handle(): void
    {
        self::$authenticatedUserId = Auth::id();
        self::$visiblePostCount = Post::count();
    }
}

class ConnectionAwareUserTeamsProbe extends UserTeams
{
    public function assignableRoleForTest(int $teamId, int $roleId): ?Role
    {
        return $this->assignableRole($teamId, $roleId);
    }

    public function canManageTeamForTest(int $teamId): bool
    {
        return $this->canManageTeam($teamId);
    }

    public function resolvedRoleForTest(string|int $userId, int $teamId): ?Role
    {
        return $this->resolvedRoleForUser($userId, $teamId);
    }

    public function userForTest(): User
    {
        return $this->user();
    }
}

class CurrentTeamConnectionSearchResource extends Resource
{
    public static ?string $slug = 'post';

    public static string $type = 'CurrentTeamConnectionSearch';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => true,
            ],
        ];
    }
}

class CurrentTeamTenantAuthUser extends User
{
    protected $connection = 'current_team_tenant';
}

class EmailIdentifiedCurrentTeamUser extends CurrentTeamTenantAuthUser
{
    public function getAuthIdentifierName(): string
    {
        return 'email';
    }

    public function getForeignKey(): string
    {
        return 'user_id';
    }
}

class NonAuraCurrentTeamActor extends FrameworkAuthenticatable
{
    protected $connection = 'current_team_tenant';

    protected $table = 'users';
}

function currentTeamTenantConnection(): Connection
{
    config()->set('database.connections.current_team_tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    DB::purge('current_team_tenant');

    $connection = DB::connection('current_team_tenant');

    Schema::connection('current_team_tenant')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->foreignId('current_team_id')->nullable();
        $table->boolean('global_admin')->default(false);
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::connection('current_team_tenant')->create('posts', function (Blueprint $table): void {
        $table->id();
        $table->text('title')->nullable();
        $table->longText('content')->nullable();
        $table->string('slug')->nullable();
        $table->string('type', 20);
        $table->string('status', 20)->default('publish')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::connection('current_team_tenant')->create('teams', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::connection('current_team_tenant')->create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->boolean('super_admin')->default(false);
        $table->json('permissions')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Schema::connection('current_team_tenant')->create('user_role', function (Blueprint $table): void {
        $table->foreignId('team_id');
        $table->foreignId('user_id');
        $table->foreignId('role_id');
        $table->timestamps();
    });

    Schema::connection('current_team_tenant')->create('meta', function (Blueprint $table): void {
        $table->id();
        $table->morphs('metable');
        $table->string('key')->nullable();
        $table->longText('value')->nullable();
    });

    Schema::connection('current_team_tenant')->create('options', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->longText('value');
        $table->foreignId('team_id');
        $table->timestamps();
    });

    Schema::connection('current_team_tenant')->create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->string('group')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
        $table->unique(['slug', 'team_id']);
    });

    return $connection;
}

function seedCurrentTeamConnection(
    Connection $connection,
    int $userId,
    int $currentTeamId,
    int $otherTeamId,
    string $label,
): User {
    $timestamp = now();

    $connection->table('users')->insert([
        'id' => $userId,
        'name' => $label.' User',
        'email' => strtolower($label).'-'.$userId.'@example.test',
        'password' => 'password',
        'current_team_id' => $currentTeamId,
        'global_admin' => false,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $connection->table('posts')->insert([
        [
            'id' => 910001,
            'title' => $label.' Current',
            'type' => 'Post',
            'status' => 'publish',
            'user_id' => $userId,
            'team_id' => $currentTeamId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 910002,
            'title' => $label.' Other',
            'type' => 'Post',
            'status' => 'publish',
            'user_id' => $userId,
            'team_id' => $otherTeamId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);

    return User::on($connection->getName())
        ->withoutGlobalScopes()
        ->findOrFail($userId);
}

/**
 * @return array{user: User, team: Team, remaining_team_id: int, role_id: int, option_name: string, invitation_id: int, post_id: int}
 */
function seedTeamDeletionConnection(Connection $connection, string $label): array
{
    $userId = 930000;
    $teamId = 930010;
    $remainingTeamId = 930011;
    $globalRoleId = 930020;
    $teamRoleId = 930021;
    $invitationId = 930030;
    $postId = 930031;
    $timestamp = now();
    $optionName = "team.{$teamId}.review-marker";

    $connection->table('users')->insert([
        'id' => $userId,
        'name' => $label.' Delete User',
        'email' => strtolower($label).'-delete@example.test',
        'password' => 'password',
        'current_team_id' => $teamId,
        'global_admin' => false,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('teams')->insert([
        [
            'id' => $teamId,
            'user_id' => $userId,
            'name' => $label.' Deleted Team',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => $remainingTeamId,
            'user_id' => $userId,
            'name' => $label.' Remaining Team',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);
    $connection->table('roles')->insert([
        [
            'id' => $globalRoleId,
            'name' => $label.' Global Role',
            'slug' => strtolower($label).'-global-role',
            'super_admin' => false,
            'permissions' => '[]',
            'team_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => $teamRoleId,
            'name' => $label.' Team Role',
            'slug' => strtolower($label).'-team-role',
            'super_admin' => false,
            'permissions' => '[]',
            'team_id' => $teamId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);
    $connection->table('user_role')->insert([
        [
            'team_id' => $teamId,
            'user_id' => $userId,
            'role_id' => $teamRoleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'team_id' => $remainingTeamId,
            'user_id' => $userId,
            'role_id' => $globalRoleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);
    $connection->table('options')->insert([
        'name' => $optionName,
        'value' => '[]',
        'team_id' => $teamId,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('meta')->insert([
        'metable_type' => Team::class,
        'metable_id' => $teamId,
        'key' => 'review-marker',
        'value' => $label,
    ]);
    $connection->table('posts')->insert([
        [
            'id' => $invitationId,
            'title' => $label.' Invitation',
            'type' => 'teaminvitation',
            'status' => 'publish',
            'user_id' => $userId,
            'team_id' => $teamId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => $postId,
            'title' => $label.' Retained Post',
            'type' => 'post',
            'status' => 'publish',
            'user_id' => $userId,
            'team_id' => $teamId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);

    return [
        'user' => User::on($connection->getName())->withoutGlobalScopes()->findOrFail($userId),
        'team' => Team::on($connection->getName())->withoutGlobalScopes()->findOrFail($teamId),
        'remaining_team_id' => $remainingTeamId,
        'role_id' => $teamRoleId,
        'option_name' => $optionName,
        'invitation_id' => $invitationId,
        'post_id' => $postId,
    ];
}

/**
 * @return array{user: User, team: Team, role: Role}
 */
function seedRoleIsolationConnection(Connection $connection, string $label, bool $superAdmin): array
{
    $userId = 940000;
    $teamId = 940010;
    $roleId = 940020;
    $timestamp = now();

    $connection->table('users')->insert([
        'id' => $userId,
        'name' => $label.' Role User',
        'email' => strtolower($label).'-role@example.test',
        'password' => 'password',
        'current_team_id' => $teamId,
        'global_admin' => false,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('teams')->insert([
        'id' => $teamId,
        'user_id' => $userId,
        'name' => $label.' Role Team',
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('roles')->insert([
        'id' => $roleId,
        'name' => $label.' Shared Role',
        'slug' => 'shared-role',
        'super_admin' => $superAdmin,
        'permissions' => json_encode([
            strtolower($label).'-only' => true,
        ]),
        'team_id' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('user_role')->insert([
        'team_id' => $teamId,
        'user_id' => $userId,
        'role_id' => $roleId,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    return [
        'user' => User::on($connection->getName())->withoutGlobalScopes()->findOrFail($userId),
        'team' => Team::on($connection->getName())->withoutGlobalScopes()->findOrFail($teamId),
        'role' => Role::on($connection->getName())->withoutGlobalScopes()->findOrFail($roleId),
    ];
}

/**
 * @return array{global_admin: User, member: User, team: Team, role: Role}
 */
function seedTeamListConnection(Connection $connection, string $label): array
{
    $globalAdminId = 950000;
    $memberId = 950001;
    $teamId = 950010;
    $roleId = 950020;
    $timestamp = now();

    $connection->table('users')->insert([
        [
            'id' => $globalAdminId,
            'name' => $label.' Global Admin',
            'email' => strtolower($label).'-global-admin@example.test',
            'password' => 'password',
            'current_team_id' => $teamId,
            'global_admin' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => $memberId,
            'name' => $label.' Member',
            'email' => strtolower($label).'-member@example.test',
            'password' => 'password',
            'current_team_id' => $teamId,
            'global_admin' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);
    $connection->table('teams')->insert([
        'id' => $teamId,
        'user_id' => $globalAdminId,
        'name' => $label.' Team',
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('roles')->insert([
        'id' => $roleId,
        'name' => $label.' Member Role',
        'slug' => 'member',
        'super_admin' => false,
        'permissions' => '[]',
        'team_id' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $connection->table('user_role')->insert([
        'team_id' => $teamId,
        'user_id' => $memberId,
        'role_id' => $roleId,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    return [
        'global_admin' => User::on($connection->getName())->withoutGlobalScopes()->findOrFail($globalAdminId),
        'member' => User::on($connection->getName())->withoutGlobalScopes()->findOrFail($memberId),
        'team' => Team::on($connection->getName())->withoutGlobalScopes()->findOrFail($teamId),
        'role' => Role::on($connection->getName())->withoutGlobalScopes()->findOrFail($roleId),
    ];
}

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team tests require the teams schema.');
    }
});

afterEach(function () {
    DB::purge('current_team_tenant');
});

it('isolates current team snapshots and cache keys by the authenticated model connection', function () {
    $userId = 910000;
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $defaultUser = seedCurrentTeamConnection($defaultConnection, $userId, 910010, 910011, 'Default');
    $tenantUser = seedCurrentTeamConnection($tenantConnection, $userId, 910020, 910021, 'Tenant');

    Auth::setUser($defaultUser);

    expect(Post::on($defaultConnection->getName())->pluck('title')->all())
        ->toBe(['Default Current']);

    Auth::setUser($tenantUser);

    expect(Post::on($tenantConnection->getName())->pluck('title')->all())
        ->toBe(['Tenant Current']);

    $defaultCacheKey = User::currentTeamCacheKey($userId, $defaultConnection);
    $tenantCacheKey = User::currentTeamCacheKey($userId, $tenantConnection);

    expect($defaultCacheKey)->not->toBe($tenantCacheKey)
        ->and(Cache::get($defaultCacheKey))->toBe(910010)
        ->and(Cache::get($tenantCacheKey))->toBe(910020);
});

it('fails closed when an authenticated Eloquent actor is not an Aura User', function () {
    $connection = currentTeamTenantConnection();
    $user = seedCurrentTeamConnection($connection, 910100, 910110, 910111, 'Non Aura Actor');
    $actor = NonAuraCurrentTeamActor::on($connection->getName())->findOrFail($user->getKey());

    Auth::setUser($actor);

    expect(Post::on($connection->getName())->count())->toBe(0);
});

it('fails closed for a colliding non-Aura actor at authorization and deletion seams', function () {
    $connection = currentTeamTenantConnection();
    $tenant = seedTeamListConnection($connection, 'Non Aura Authorization');
    $actor = NonAuraCurrentTeamActor::on($connection->getName())
        ->findOrFail($tenant['global_admin']->getKey());

    Auth::setUser($actor);

    $component = app(ConnectionAwareUserTeamsProbe::class);
    $component->userId = $tenant['member']->getKey();
    $resource = (new Post)->setConnection($connection->getName());

    expect($component->canManageTeamForTest($tenant['team']->getKey()))->toBeFalse()
        ->and(app(ResourcePolicy::class)->viewAny($actor, $resource))->toBeFalse();

    expect(fn () => Post::on($connection->getName())->withoutGlobalScopes()->create([
        'title' => 'Non Aura Resource Write',
    ]))->toThrow(LogicException::class, 'authenticated actor and resource');

    expect(fn () => $tenant['team']->delete())
        ->toThrow(LogicException::class, 'Only authenticated Aura users may delete resources.');
});

it('rejects team writes from a colliding non-Aura actor', function () {
    $connection = currentTeamTenantConnection();
    $tenant = seedTeamListConnection($connection, 'Non Aura Team Hook');
    $actor = NonAuraCurrentTeamActor::on($connection->getName())
        ->findOrFail($tenant['global_admin']->getKey());

    Auth::setUser($actor);

    expect(fn () => Team::on($connection->getName())->create([
        'name' => 'Non Aura Unowned Team',
        'user_id' => $actor->getKey(),
    ]))->toThrow(LogicException::class, 'Only authenticated Aura users may create or update teams.');

    expect($connection->table('teams')
        ->where('name', 'Non Aura Unowned Team')
        ->exists())->toBeFalse()
        ->and($connection->table('users')
            ->where('id', $actor->getKey())
            ->value('current_team_id'))->toBe($tenant['team']->getKey());
});

it('rejects role escalation changes from a colliding non-Aura actor', function () {
    $connection = currentTeamTenantConnection();
    $tenant = seedRoleIsolationConnection($connection, 'Non Aura Role Field', true);
    $actor = NonAuraCurrentTeamActor::on($connection->getName())
        ->findOrFail($tenant['user']->getKey());

    Auth::setUser($actor);

    expect(fn () => app(RolesField::class)->saved($tenant['user'], [], []))
        ->toThrow(HttpException::class);

    expect($connection->table('user_role')
        ->where('team_id', $tenant['team']->getKey())
        ->where('user_id', $tenant['user']->getKey())
        ->where('role_id', $tenant['role']->getKey())
        ->exists())->toBeTrue();
});

it('rejects ordinary role changes from a colliding non-Aura actor', function () {
    $connection = currentTeamTenantConnection();
    $tenant = seedTeamListConnection($connection, 'Non Aura Ordinary Role');
    $replacementRoleId = $tenant['role']->getKey() + 1;
    $connection->table('roles')->insert([
        'id' => $replacementRoleId,
        'name' => 'Replacement Member',
        'slug' => 'replacement-member',
        'super_admin' => false,
        'permissions' => '[]',
        'team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $actor = NonAuraCurrentTeamActor::on($connection->getName())
        ->findOrFail($tenant['global_admin']->getKey());

    Auth::setUser($actor);

    expect(fn () => app(RolesField::class)->saved($tenant['member'], [], [$replacementRoleId]))
        ->toThrow(HttpException::class);

    expect($connection->table('user_role')
        ->where('team_id', $tenant['team']->getKey())
        ->where('user_id', $tenant['member']->getKey())
        ->value('role_id'))->toBe($tenant['role']->getKey());
});

it('fails closed when an actor or explicit team context queries another connection with colliding ids', function () {
    $userId = 911000;
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $defaultUser = seedCurrentTeamConnection($defaultConnection, $userId, 911010, 911011, 'Default Bound');
    seedCurrentTeamConnection($tenantConnection, $userId, 911010, 911011, 'Tenant Bound');

    Auth::setUser($defaultUser);

    expect(Post::on($tenantConnection->getName())->count())->toBe(0)
        ->and(TeamScope::forTeam(
            911010,
            fn (): int => Post::on($tenantConnection->getName())->count(),
            $defaultConnection,
        ))->toBe(0);
});

it('never reuses a current-team namespace after its epoch marker is evicted', function () {
    $user = createSuperAdmin();
    $connection = $user->getConnection();
    $epochKey = User::connectionScopedCacheKey(
        'current_team_generation_user_'.$user->getKey(),
        $connection,
    );

    Cache::forever($epochKey, 1);

    $oldCacheKey = User::currentTeamCacheKey($user->getKey(), $connection);
    Cache::put($oldCacheKey, 'surviving stale value', now()->addDay());

    expect(Cache::has($oldCacheKey))->toBeTrue();

    Cache::forget($epochKey);
    Aura::flushState();

    $newCacheKey = User::currentTeamCacheKey($user->getKey(), $connection);

    expect($newCacheKey)->not->toBe($oldCacheKey)
        ->and(Cache::get($oldCacheKey))->toBe('surviving stale value');
});

it('invalidates the current team cache when a persisted user has id zero', function () {
    $tenantConnection = currentTeamTenantConnection();
    $tenantUser = seedCurrentTeamConnection($tenantConnection, 0, 915000, 915001, 'Zero');
    $cacheKey = User::currentTeamCacheKey(0, $tenantConnection);

    Auth::setUser($tenantUser);

    expect(Post::on($tenantConnection->getName())->pluck('title')->all())
        ->toBe(['Zero Current'])
        ->and(Cache::get($cacheKey))->toBe(915000);

    $tenantUser->forceFill(['current_team_id' => 915001])->save();

    expect(Cache::has($cacheKey))->toBeFalse()
        ->and(Post::on($tenantConnection->getName())->pluck('title')->all())
        ->toBe(['Zero Other']);
});

it('resolves memberships roles and catalog generations on the user model connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedRoleIsolationConnection($defaultConnection, 'Default', true);
    $tenant = seedRoleIsolationConnection($tenantConnection, 'Tenant', false);

    Auth::setUser($tenant['user']);

    expect($tenant['user']->isSuperAdmin())->toBeFalse()
        ->and($tenant['user']->hasPermission('tenant-only'))->toBeTrue()
        ->and($tenant['user']->hasPermission('default-only'))->toBeFalse()
        ->and($tenant['user']->cachedRoles()->first()->getConnection()->getName())
        ->toBe($tenantConnection->getName());

    $resolvedTenantRole = Role::resolveForTeam('shared-role', $tenant['team']->getKey(), $tenantConnection);

    expect($resolvedTenantRole?->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($resolvedTenantRole?->getAttribute('super_admin'))->toBeFalse();

    Auth::setUser($default['user']);

    expect($default['user']->isSuperAdmin())->toBeTrue()
        ->and($default['user']->hasPermission('default-only'))->toBeTrue();

    $tenantCatalogVersion = Role::catalogVersion($tenantConnection);
    $defaultCatalogVersion = Role::catalogVersion($defaultConnection);

    $default['role']->forceFill(['name' => 'Default Changed Role'])->save();

    expect(Role::catalogVersion($defaultConnection))->toBe($defaultCatalogVersion + 1)
        ->and(Role::catalogVersion($tenantConnection))->toBe($tenantCatalogVersion);
});

it('rejects a same-id team model from another database in membership and switching checks', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedRoleIsolationConnection($defaultConnection, 'Default', false);
    $tenant = seedRoleIsolationConnection($tenantConnection, 'Tenant', false);

    Auth::setUser($tenant['user']);
    $tenant['user']->unsetRelation('teams');

    expect(User::connectionCacheIdentity($tenant['user']->teams()->getRelated()->getConnection()))
        ->toBe(User::connectionCacheIdentity($tenantConnection))
        ->and($tenant['user']->belongsToTeam($tenant['team']))->toBeTrue()
        ->and($tenant['user']->belongsToTeam($default['team']))->toBeFalse()
        ->and($tenant['user']->switchTeam($default['team']))->toBeFalse()
        ->and($tenantConnection->table('users')->where('id', $tenant['user']->getKey())->value('current_team_id'))
        ->toBe($tenant['team']->getKey());
});

it('resolves a team-switch scalar id on the authenticated user database', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedRoleIsolationConnection($defaultConnection, 'Default', false);
    $tenant = seedRoleIsolationConnection($tenantConnection, 'Tenant', false);

    $this->actingAs($tenant['user'])
        ->put(route('aura.current-team.update'), ['team_id' => $tenant['team']->getKey()])
        ->assertRedirect(route('aura.dashboard'));

    expect($default['team']->getKey())->toBe($tenant['team']->getKey())
        ->and($tenant['user']->fresh()->current_team_id)->toBe($tenant['team']->getKey());
});

it('resolves guest invitation scalar ids through the configured resource connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $invitationId = 960030;
    $timestamp = now();

    foreach ([
        [$defaultConnection, $default['member']->getAttribute('email')],
        [$tenantConnection, 'tenant-guest@example.test'],
    ] as [$connection, $email]) {
        $connection->table('posts')->insert([
            'id' => $invitationId,
            'title' => null,
            'type' => TeamInvitation::$type,
            'status' => 'publish',
            'user_id' => $default['global_admin']->getKey(),
            'team_id' => $default['team']->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $connection->table('meta')->insert([
            [
                'metable_type' => TeamInvitation::class,
                'metable_id' => $invitationId,
                'key' => 'email',
                'value' => $email,
            ],
            [
                'metable_type' => TeamInvitation::class,
                'metable_id' => $invitationId,
                'key' => 'role',
                'value' => $default['role']->getKey(),
            ],
        ]);
    }

    $teamResource = new Team;
    $teamResource->setConnection($tenantConnection->getName());
    app()->instance(config('aura.resources.team'), $teamResource);

    $invitationResource = new TeamInvitation;
    $invitationResource->setConnection($tenantConnection->getName());
    app()->instance(config('aura.resources.team-invitation'), $invitationResource);

    config(['aura.auth.user_invitations' => true]);
    Auth::logout();

    $url = URL::signedRoute('aura.invitation.register', [
        'team' => $tenant['team']->getKey(),
        'teamInvitation' => $invitationId,
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Tenant Team');
});

it('binds mailed guest invitation links to an allowed signed connection identity', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Mail');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Mail');
    $invitationId = 961030;
    $timestamp = now();

    foreach ([
        [$defaultConnection, $default['member']->getAttribute('email')],
        [$tenantConnection, 'tenant-mailed-guest@example.test'],
    ] as [$connection, $email]) {
        $connection->table('posts')->insert([
            'id' => $invitationId,
            'title' => null,
            'type' => TeamInvitation::$type,
            'status' => 'publish',
            'user_id' => $default['global_admin']->getKey(),
            'team_id' => $default['team']->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $connection->table('meta')->insert([
            [
                'metable_type' => TeamInvitation::class,
                'metable_id' => $invitationId,
                'key' => 'email',
                'value' => $email,
            ],
            [
                'metable_type' => TeamInvitation::class,
                'metable_id' => $invitationId,
                'key' => 'role',
                'value' => $default['role']->getKey(),
            ],
        ]);
    }

    config([
        'aura.auth.user_invitations' => true,
        'aura.auth.invitation_connections' => [$defaultConnection->getName(), $tenantConnection->getName()],
    ]);
    Auth::logout();

    $invitation = TeamInvitation::on($tenantConnection->getName())
        ->withoutGlobalScopes()
        ->findOrFail($invitationId);
    $mail = (new TeamInvitationMail($invitation))->build();
    $url = $mail->viewData['registerUrl'];
    $acceptUrl = $mail->viewData['acceptUrl'];

    $this->get($url)
        ->assertOk()
        ->assertSee('Tenant Mail Team');

    $tenantConnection->table('users')
        ->where('id', $tenant['member']->getKey())
        ->update(['email' => 'tenant-mailed-guest@example.test']);

    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT NULL, type TEXT, status TEXT, user_id INTEGER NULL, team_id INTEGER NULL, created_at TEXT NULL, updated_at TEXT NULL, deleted_at TEXT NULL)');
    $readPdo->exec('CREATE TABLE meta (id INTEGER PRIMARY KEY, metable_type TEXT, metable_id INTEGER, key TEXT NULL, value TEXT NULL)');
    $readPdo->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, description TEXT NULL, super_admin INTEGER NOT NULL DEFAULT 0, permissions TEXT NULL, user_id INTEGER NULL, team_id INTEGER NULL, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec('CREATE TABLE user_role (team_id INTEGER, user_id INTEGER, role_id INTEGER, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec("INSERT INTO posts (id, type, status, team_id) VALUES ({$invitationId}, 'teaminvitation', 'publish', {$tenant['team']->getKey()})");
    $tenantConnection->setReadPdo($readPdo);

    $this->actingAs($tenant['member'])
        ->get($acceptUrl)
        ->assertRedirect(route('aura.dashboard'));

    expect($tenantConnection->table('posts')->useWritePdo()->where('id', $invitationId)->exists())->toBeFalse()
        ->and($defaultConnection->table('posts')->where('id', $invitationId)->exists())->toBeTrue();

    Auth::logout();
    $tamperedUrl = str_replace(
        'invitation_connection=current_team_tenant',
        'invitation_connection=sqlite',
        $url,
    );
    $this->get($tamperedUrl)->assertForbidden();
});

it('creates an authorized global permission only on the supplied resource connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $slug = 'tenant-authorized-global';

    Auth::setUser($tenant['global_admin']);

    $permission = Permission::createGlobal([
        'name' => 'Tenant Authorized Global',
        'slug' => $slug,
        'group' => 'Connection',
    ], $tenantConnection);
    $requestPermission = Permission::createGlobal([
        'name' => 'Tenant Request Global',
        'slug' => 'tenant-request-global',
        'group' => 'Connection',
    ]);

    expect($permission->getConnectionName())->toBe($tenantConnection->getName())
        ->and($requestPermission->getConnectionName())->toBe($tenantConnection->getName())
        ->and($tenantConnection->table('permissions')->where('slug', $slug)->exists())->toBeTrue()
        ->and($tenantConnection->table('permissions')->where('slug', 'tenant-request-global')->exists())->toBeTrue()
        ->and($defaultConnection->table('permissions')->where('slug', $slug)->exists())->toBeFalse()
        ->and($defaultConnection->table('permissions')->where('slug', 'tenant-request-global')->exists())->toBeFalse()
        ->and($default['team']->getKey())->toBe($tenant['team']->getKey());
});

it('rejects global and ordinary Livewire creates when authorization and writes use different connections', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Write');
    seedTeamListConnection($tenantConnection, 'Tenant Write');

    Auth::setUser($default['global_admin']);

    expect(fn () => Permission::createGlobal([
        'name' => 'Cross connection global',
        'slug' => 'cross-connection-global',
        'group' => 'Security',
    ], $tenantConnection))->toThrow(AuthorizationException::class);

    Aura::fake();
    Aura::registerResources([CurrentTeamConnectionSearchResource::class]);
    $tenantResource = new CurrentTeamConnectionSearchResource;
    $tenantResource->setConnection($tenantConnection->getName());
    app()->instance(CurrentTeamConnectionSearchResource::class, $tenantResource);

    livewire(Create::class, ['slug' => 'post'])->assertForbidden();

    expect($tenantConnection->table('permissions')->where('slug', 'cross-connection-global')->exists())
        ->toBeFalse();
});

it('rejects every resource policy ability before cross-connection admin shortcuts', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Policy');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Policy');
    $tenantPermission = Permission::createGlobalForSystem([
        'name' => 'Tenant policy permission',
        'slug' => 'tenant-policy-permission',
        'group' => 'Security',
    ], $tenantConnection);
    $tenantResource = new Permission;
    $tenantResource->setConnection($tenantConnection->getName());

    foreach (['create', 'createGlobal', 'viewAny'] as $ability) {
        expect(Gate::forUser($default['global_admin'])->denies($ability, $tenantResource))->toBeTrue();
    }

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect(Gate::forUser($default['global_admin'])->denies($ability, $tenantPermission))->toBeTrue()
            ->and(fn () => Gate::forUser($default['global_admin'])->authorize($ability, $tenantPermission))
            ->toThrow(AuthorizationException::class);
    }

    Auth::setUser($default['global_admin']);

    expect(fn () => $tenantPermission->moveGlobalToTeam($tenant['team']->getKey()))
        ->toThrow(AuthorizationException::class)
        ->and($tenantPermission->fresh()->team_id)->toBeNull();

    foreach (['create', 'createGlobal', 'viewAny'] as $ability) {
        expect(Gate::forUser($tenant['global_admin'])->allows($ability, $tenantResource))->toBeTrue();
    }

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect(Gate::forUser($tenant['global_admin'])->allows($ability, $tenantPermission))->toBeTrue();
    }
});

it('keeps container-connected non-user global search queries on that database', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $postId = 970030;
    $timestamp = now();

    foreach ([
        [$defaultConnection, 'Default Connection Needle'],
        [$tenantConnection, 'Tenant Connection Needle'],
    ] as [$connection, $title]) {
        $connection->table('posts')->insert([
            'id' => $postId,
            'title' => $title,
            'type' => CurrentTeamConnectionSearchResource::$type,
            'status' => 'publish',
            'user_id' => $default['global_admin']->getKey(),
            'team_id' => $default['team']->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    Aura::fake();
    Aura::registerResources([CurrentTeamConnectionSearchResource::class]);

    $searchResource = new CurrentTeamConnectionSearchResource;
    $searchResource->setConnection($tenantConnection->getName());
    app()->instance(CurrentTeamConnectionSearchResource::class, $searchResource);

    if (! Route::has('aura.post.view')) {
        Route::get('/current-team-connection-search/{id}', fn () => null)
            ->name('aura.post.view');
    }

    Auth::setUser($tenant['global_admin']);

    $globalSearch = new GlobalSearch;
    $globalSearch->search = 'Connection Needle';

    expect($globalSearch->getSearchResultsProperty()->flatten(1)->pluck('title')->all())
        ->toBe(['Tenant Connection Needle'])
        ->and($default['team']->getKey())->toBe($tenant['team']->getKey());
});

it('fails closed for non-user global search on a different connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Search Bound');
    seedTeamListConnection($tenantConnection, 'Tenant Search Bound');

    Aura::fake();
    Aura::registerResources([CurrentTeamConnectionSearchResource::class]);
    $searchResource = new CurrentTeamConnectionSearchResource;
    $searchResource->setConnection($tenantConnection->getName());
    app()->instance(CurrentTeamConnectionSearchResource::class, $searchResource);
    Auth::setUser($default['global_admin']);

    $globalSearch = new GlobalSearch;
    $globalSearch->search = 'Needle';

    expect($globalSearch->getSearchResultsProperty())->toBeEmpty();
});

it('isolates global and member team lists and rename invalidation by connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $userOptionName = 'user.'.$default['member']->getKey().'.connection-check';
    $teamOptionName = 'team.'.$default['team']->getKey().'.connection-check';

    foreach ([[$defaultConnection, 'Default'], [$tenantConnection, 'Tenant']] as [$connection, $label]) {
        $connection->table('options')->insert([
            [
                'name' => $userOptionName,
                'value' => json_encode([$label.' User Option']),
                'team_id' => $default['team']->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => $teamOptionName,
                'value' => json_encode([$label.' Team Option']),
                'team_id' => $default['team']->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    Auth::setUser($default['global_admin']);
    $defaultGlobalTeams = $default['global_admin']->getTeams();

    Auth::setUser($default['member']);
    $defaultMemberTeams = $default['member']->getTeams();

    Auth::setUser($tenant['global_admin']);
    $tenantGlobalTeams = $tenant['global_admin']->getTeams();

    Auth::setUser($tenant['member']);
    $tenantMemberTeams = $tenant['member']->getTeams();
    Auth::setUser($default['member']);
    $defaultAuraOption = Aura::getOption('connection-check');
    Auth::setUser($tenant['member']);
    $tenantAuraOption = Aura::getOption('connection-check');

    Auth::setUser($default['member']);
    $defaultUserOption = $default['member']->getOption('connection-check');
    $defaultTeamOption = $default['team']->getOption('connection-check');
    Auth::setUser($tenant['member']);
    $tenantUserOption = $tenant['member']->getOption('connection-check');
    $tenantTeamOption = $tenant['team']->getOption('connection-check');

    expect($defaultGlobalTeams->pluck('name')->all())->toBe(['Default Team'])
        ->and($defaultMemberTeams->pluck('name')->all())->toBe(['Default Team'])
        ->and($tenantGlobalTeams->pluck('name')->all())->toBe(['Tenant Team'])
        ->and($tenantMemberTeams->pluck('name')->all())->toBe(['Tenant Team'])
        ->and($tenantGlobalTeams->first()->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($tenantMemberTeams->first()->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($defaultUserOption)->toBe(['Default User Option'])
        ->and($tenantUserOption)->toBe(['Tenant User Option'])
        ->and($defaultTeamOption)->toBe(['Default Team Option'])
        ->and($tenantTeamOption)->toBe(['Tenant Team Option'])
        ->and($defaultAuraOption)->toBe(['Default Team Option'])
        ->and($tenantAuraOption)->toBe(['Tenant Team Option'])
        ->and(User::connectionScopedCacheKey($userOptionName, $defaultConnection))
        ->not->toBe(User::connectionScopedCacheKey($userOptionName, $tenantConnection));

    $defaultConnection->table('teams')
        ->where('id', $default['team']->getKey())
        ->update(['name' => 'Default Changed Behind Cache']);

    $tenant['team']->forceFill(['name' => 'Tenant Renamed'])->save();

    Auth::setUser($tenant['global_admin']);
    expect($tenant['global_admin']->getTeams()->pluck('name')->all())->toBe(['Tenant Renamed']);

    Auth::setUser($tenant['member']);
    expect($tenant['member']->getTeams()->pluck('name')->all())->toBe(['Tenant Renamed']);

    Auth::setUser($default['global_admin']);
    expect($default['global_admin']->getTeams()->pluck('name')->all())->toBe(['Default Team']);

    Auth::setUser($default['member']);
    expect($default['member']->getTeams()->pluck('name')->all())->toBe(['Default Team']);
});

it('creates team memberships and catalog roles only on the team connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');

    Auth::setUser($default['member']);
    expect($default['member']->getTeams()->pluck('name')->all())->toBe(['Default Team']);

    $timestamp = now();
    $defaultConnection->table('teams')->insert([
        'id' => 950011,
        'user_id' => $default['member']->getKey(),
        'name' => 'Default Hidden Team',
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $defaultConnection->table('user_role')->insert([
        'team_id' => 950011,
        'user_id' => $default['member']->getKey(),
        'role_id' => $default['role']->getKey(),
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $defaultRoleCount = $defaultConnection->table('roles')->count();
    $defaultPermissionCount = $defaultConnection->table('permissions')->count();

    Auth::setUser($tenant['member']);
    expect($tenant['member']->getTeams()->pluck('name')->all())->toBe(['Tenant Team']);

    $newTenantTeam = Team::on($tenantConnection->getName())->create([
        'name' => 'Tenant Attached Team',
        'user_id' => $tenant['member']->getKey(),
    ]);
    $tenantAdminRoleId = $tenantConnection->table('user_role')
        ->where('team_id', $newTenantTeam->getKey())
        ->where('user_id', $tenant['member']->getKey())
        ->value('role_id');

    expect($tenantConnection->table('roles')->where('id', $tenantAdminRoleId)->value('slug'))->toBe('admin')
        ->and($defaultConnection->table('roles')->count())->toBe($defaultRoleCount)
        ->and($tenantConnection->table('permissions')->where('team_id', $newTenantTeam->getKey())->count())->toBeGreaterThan(0)
        ->and($defaultConnection->table('permissions')->count())->toBe($defaultPermissionCount)
        ->and($tenant['member']->getTeams()->pluck('name')->all())
        ->toBe(['Tenant Team', 'Tenant Attached Team']);

    Auth::setUser($default['member']);
    expect($default['member']->getTeams()->pluck('name')->all())->toBe(['Default Team']);
});

it('does not attach a same-id actor from another connection when creating a team', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');

    Auth::setUser($default['member']);

    $newTenantTeam = Team::on($tenantConnection->getName())->create([
        'name' => 'Tenant Team Without Actor',
        'user_id' => $tenant['member']->getKey(),
    ]);

    expect($tenantConnection->table('user_role')
        ->where('team_id', $newTenantTeam->getKey())
        ->where('user_id', $tenant['member']->getKey())
        ->exists())->toBeFalse()
        ->and($tenantConnection->table('users')
            ->where('id', $tenant['member']->getKey())
            ->value('current_team_id'))->toBe($tenant['team']->getKey())
        ->and($defaultConnection->table('users')
            ->where('id', $default['member']->getKey())
            ->value('current_team_id'))->toBe($default['team']->getKey());
});

it('does not carry an authenticated team id into permission generation on another connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');

    Auth::setUser($default['member']);

    GenerateAllResourcePermissions::dispatchSync(null, $tenantConnection->getName());

    expect($tenantConnection->table('permissions')
        ->where('team_id', $tenant['team']->getKey())
        ->exists())->toBeFalse()
        ->and($tenantConnection->table('permissions')->whereNull('team_id')->count())->toBeGreaterThan(0)
        ->and($defaultConnection->table('permissions')->count())->toBe(0);
});

it('refuses all-resource permission generation when its named database identity changes', function () {
    $tenantConnection = currentTeamTenantConnection();
    $job = new GenerateAllResourcePermissions(null, $tenantConnection->getName());
    $replacementDatabase = tempnam(sys_get_temp_dir(), 'aura-all-connection-drift-');

    expect($replacementDatabase)->toBeString();

    try {
        Aura::fake();
        Aura::registerResources([]);
        config()->set('database.connections.current_team_tenant.database', $replacementDatabase);
        DB::purge('current_team_tenant');

        expect(fn () => $job->handle())
            ->toThrow(RuntimeException::class, 'database connection identity changed');
    } finally {
        DB::purge('current_team_tenant');
        @unlink($replacementDatabase);
    }
});

it('validates user email uniqueness on the authenticated model connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $email = 'connection-only@example.test';

    $defaultConnection->table('users')->insert([
        'name' => 'Default Collision',
        'email' => $email,
        'password' => 'password',
        'current_team_id' => $default['team']->getKey(),
        'global_admin' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Auth::setUser($tenant['member']);

    expect(Validator::make(
        ['email' => $email],
        ['email' => [new CaseInsensitiveUniqueEmail]],
    )->passes())->toBeTrue();

    $tenantConnection->table('users')->insert([
        'name' => 'Tenant Collision',
        'email' => $email,
        'password' => 'password',
        'current_team_id' => $tenant['team']->getKey(),
        'global_admin' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Validator::make(
        ['email' => strtoupper($email)],
        ['email' => [new CaseInsensitiveUniqueEmail]],
    )->fails())->toBeTrue();
});

it('keeps membership editor lookups and role-field writes on the user connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant');
    $tenantOnlyRoleId = 950021;

    $tenantConnection->table('roles')->insert([
        'id' => $tenantOnlyRoleId,
        'name' => 'Tenant Only Role',
        'slug' => 'tenant-only-role',
        'super_admin' => false,
        'permissions' => '[]',
        'team_id' => $tenant['team']->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Auth::setUser($tenant['global_admin']);

    $component = app(ConnectionAwareUserTeamsProbe::class);
    $component->userId = $tenant['member']->getKey();

    expect($component->userForTest()->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($component->resolvedRoleForTest($tenant['member']->getKey(), $tenant['team']->getKey())?->getAttribute('name'))
        ->toBe('Tenant Member Role')
        ->and($component->assignableRoleForTest($tenant['team']->getKey(), $tenantOnlyRoleId)?->getConnection()->getName())
        ->toBe($tenantConnection->getName());

    app(RolesField::class)->saved($tenant['member'], [], [$tenantOnlyRoleId]);

    expect($tenantConnection->table('user_role')
        ->where('user_id', $tenant['member']->getKey())
        ->where('team_id', $tenant['team']->getKey())
        ->value('role_id'))->toBe($tenantOnlyRoleId)
        ->and($defaultConnection->table('user_role')
            ->where('user_id', $default['member']->getKey())
            ->where('team_id', $default['team']->getKey())
            ->value('role_id'))->toBe($default['role']->getKey());
});

it('uses writer membership and catalog state for membership-editor authorization', function () {
    $connection = currentTeamTenantConnection();
    $writer = seedRoleIsolationConnection($connection, 'Writer Membership', true);
    $userId = $writer['user']->getKey();
    $teamId = $writer['team']->getKey();
    $roleId = $writer['role']->getKey();
    $readerOnlyRoleId = $roleId + 1;
    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, current_team_id INTEGER NULL, global_admin INTEGER NOT NULL DEFAULT 0)');
    $readPdo->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, description TEXT NULL, super_admin INTEGER NOT NULL DEFAULT 0, permissions TEXT NULL, user_id INTEGER NULL, team_id INTEGER NULL, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec('CREATE TABLE user_role (team_id INTEGER, user_id INTEGER, role_id INTEGER, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec("INSERT INTO users (id, current_team_id, global_admin) VALUES ({$userId}, {$teamId}, 0)");
    $readPdo->exec("INSERT INTO roles (id, name, slug, super_admin, permissions, team_id) VALUES ({$roleId}, 'Stale Super Admin', 'shared-role', 1, '[]', NULL)");
    $readPdo->exec("INSERT INTO roles (id, name, slug, super_admin, permissions, team_id) VALUES ({$readerOnlyRoleId}, 'Reader Only Role', 'reader-only-role', 0, '[]', {$teamId})");
    $readPdo->exec("INSERT INTO user_role (team_id, user_id, role_id) VALUES ({$teamId}, {$userId}, {$roleId})");

    $connection->table('user_role')
        ->where('team_id', $teamId)
        ->where('user_id', $userId)
        ->delete();
    $connection->setReadPdo($readPdo);
    Auth::setUser($writer['user']);

    $component = app(ConnectionAwareUserTeamsProbe::class);

    expect($component->canManageTeamForTest($teamId))->toBeFalse()
        ->and($component->assignableRoleForTest($teamId, $readerOnlyRoleId))->toBeNull();
});

it('keeps team deletion cleanup on the deleted model connection across rollback and commit', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamDeletionConnection($defaultConnection, 'Default');
    $tenant = seedTeamDeletionConnection($tenantConnection, 'Tenant');
    $tenantCacheKey = User::currentTeamCacheKey($tenant['user']->getKey(), $tenantConnection);
    $defaultCacheKey = User::currentTeamCacheKey($default['user']->getKey(), $defaultConnection);

    Cache::put($tenantCacheKey, $tenant['team']->getKey());
    Cache::put($defaultCacheKey, $default['team']->getKey());
    Auth::setUser($tenant['user']);

    $tenantConnection->beginTransaction();
    $tenant['team']->delete();

    expect($tenantConnection->table('users')->where('id', $tenant['user']->getKey())->value('current_team_id'))
        ->toBe($tenant['remaining_team_id'])
        ->and($tenantConnection->table('user_role')->where('team_id', $tenant['team']->getKey())->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('roles')->where('id', $tenant['role_id'])->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('options')->where('name', $tenant['option_name'])->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('meta')->where('metable_id', $tenant['team']->getKey())->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('posts')->where('id', $tenant['invitation_id'])->whereNull('deleted_at')->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('posts')->where('id', $tenant['post_id'])->exists())
        ->toBeTrue()
        ->and(Cache::get($tenantCacheKey))->toBe($tenant['team']->getKey())
        ->and($defaultConnection->table('users')->where('id', $default['user']->getKey())->value('current_team_id'))
        ->toBe($default['team']->getKey())
        ->and($defaultConnection->table('user_role')->where('team_id', $default['team']->getKey())->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('roles')->where('id', $default['role_id'])->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('options')->where('name', $default['option_name'])->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('meta')->where('metable_id', $default['team']->getKey())->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('posts')->where('id', $default['invitation_id'])->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('posts')->where('id', $default['post_id'])->exists())
        ->toBeTrue()
        ->and(Cache::get($defaultCacheKey))->toBe($default['team']->getKey());

    $tenantConnection->rollBack();

    expect($tenantConnection->table('users')->where('id', $tenant['user']->getKey())->value('current_team_id'))
        ->toBe($tenant['team']->getKey())
        ->and($tenantConnection->table('user_role')->where('team_id', $tenant['team']->getKey())->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('roles')->where('id', $tenant['role_id'])->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('options')->where('name', $tenant['option_name'])->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('meta')->where('metable_id', $tenant['team']->getKey())->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('posts')->where('id', $tenant['invitation_id'])->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('posts')->where('id', $tenant['post_id'])->exists())
        ->toBeTrue()
        ->and(Cache::get($tenantCacheKey))->toBe($tenant['team']->getKey());

    $tenantConnection->beginTransaction();
    Team::on($tenantConnection->getName())
        ->withoutGlobalScopes()
        ->findOrFail($tenant['team']->getKey())
        ->delete();
    $tenantConnection->commit();

    expect($tenantConnection->table('users')->where('id', $tenant['user']->getKey())->value('current_team_id'))
        ->toBe($tenant['remaining_team_id'])
        ->and($tenantConnection->table('user_role')->where('team_id', $tenant['team']->getKey())->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('roles')->where('id', $tenant['role_id'])->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('options')->where('name', $tenant['option_name'])->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('meta')->where('metable_id', $tenant['team']->getKey())->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('posts')->where('id', $tenant['invitation_id'])->whereNull('deleted_at')->exists())
        ->toBeFalse()
        ->and($tenantConnection->table('posts')->where('id', $tenant['post_id'])->exists())
        ->toBeTrue()
        ->and(Cache::has($tenantCacheKey))->toBeFalse()
        ->and($defaultConnection->table('users')->where('id', $default['user']->getKey())->value('current_team_id'))
        ->toBe($default['team']->getKey())
        ->and($defaultConnection->table('user_role')->where('team_id', $default['team']->getKey())->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('roles')->where('id', $default['role_id'])->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('options')->where('name', $default['option_name'])->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('meta')->where('metable_id', $default['team']->getKey())->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('posts')->where('id', $default['invitation_id'])->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('posts')->where('id', $default['post_id'])->exists())
        ->toBeTrue()
        ->and(Cache::get($defaultCacheKey))->toBe($default['team']->getKey());
});

it('keeps nested tenant transaction invalidation on its own connection until the outer boundary', function () {
    $userId = 920000;
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $defaultUser = seedCurrentTeamConnection($defaultConnection, $userId, 920010, 920011, 'Default Tx');
    $tenantUser = seedCurrentTeamConnection($tenantConnection, $userId, 920020, 920021, 'Tenant Tx');
    $defaultCacheKey = User::currentTeamCacheKey($userId, $defaultConnection);
    $tenantCacheKey = User::currentTeamCacheKey($userId, $tenantConnection);

    Auth::setUser($defaultUser);
    expect(Post::on($defaultConnection->getName())->pluck('title')->all())->toBe(['Default Tx Current']);

    Auth::setUser($tenantUser);
    expect(Post::on($tenantConnection->getName())->pluck('title')->all())->toBe(['Tenant Tx Current']);

    $tenantConnection->beginTransaction();
    $tenantConnection->beginTransaction();

    $tenantUser->forceFill(['current_team_id' => 920021])->save();
    $tenantConnection->commit();

    expect(Post::on($tenantConnection->getName())->pluck('title')->all())->toBe(['Tenant Tx Other'])
        ->and(Cache::get($tenantCacheKey))->toBe(920020)
        ->and(Cache::get($defaultCacheKey))->toBe(920010);

    $tenantConnection->rollBack();
    Aura::flushState();
    $tenantUser->refresh();

    expect(Post::on($tenantConnection->getName())->pluck('title')->all())->toBe(['Tenant Tx Current'])
        ->and(Cache::get($tenantCacheKey))->toBe(920020)
        ->and(Cache::get($defaultCacheKey))->toBe(920010);

    $tenantConnection->beginTransaction();
    $tenantConnection->beginTransaction();
    $tenantUser->forceFill(['current_team_id' => 920021])->save();
    $tenantConnection->commit();

    expect(Cache::get($tenantCacheKey))->toBe(920020)
        ->and(Cache::get($defaultCacheKey))->toBe(920010);

    $tenantConnection->commit();

    expect(Cache::has($tenantCacheKey))->toBeFalse()
        ->and(Cache::get($defaultCacheKey))->toBe(920010)
        ->and(Post::on($tenantConnection->getName())->pluck('title')->all())->toBe(['Tenant Tx Other'])
        ->and(Cache::get(User::currentTeamCacheKey($userId, $tenantConnection)))->toBe(920021);
});

beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::flush();
    ObserveGuestQueueWorkerJob::$authenticatedUserId = null;
    ObserveGuestQueueWorkerJob::$visiblePostCount = null;
});

it('uses the new current team after switching with a warmed team scope cache', function () {
    $user = createSuperAdmin();
    $firstTeam = Team::find($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Second Cache Team',
        'user_id' => $user->id,
    ]);

    $user->refresh();
    expect($user->switchTeam($firstTeam))->toBeTrue();

    $firstTeamPost = createPost([
        'title' => 'First Team Post',
        'team_id' => $firstTeam->id,
        'user_id' => $user->id,
    ]);
    $secondTeamPost = createPost([
        'title' => 'Second Team Post',
        'team_id' => $secondTeam->id,
        'user_id' => $user->id,
    ]);

    $cacheKey = User::currentTeamCacheKey($user->id);

    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();
    expect(Cache::get($cacheKey))->toBe($firstTeam->id);

    expect($user->switchTeam($secondTeam))->toBeTrue();
    expect(Cache::has($cacheKey))->toBeFalse();

    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeFalse();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();
    expect(Cache::get(User::currentTeamCacheKey($user->id)))->toBe($secondTeam->id);
});

it('retires bounded namespaces across repeated switches and fresh containers', function () {
    $user = createSuperAdmin();
    $firstTeam = Team::findOrFail($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Repeated Cache Team',
        'user_id' => $user->id,
    ]);
    $retiredCacheKeys = [];

    $this->actingAs($user);

    foreach ([$secondTeam, $firstTeam, $secondTeam, $firstTeam] as $team) {
        Aura::flushState();

        $retiredCacheKey = User::currentTeamCacheKey($user->id);

        Post::count();

        expect(Cache::has($retiredCacheKey))->toBeTrue()
            ->and($user->switchTeam($team))->toBeTrue()
            ->and(Cache::has($retiredCacheKey))->toBeFalse();

        $retiredCacheKeys[] = $retiredCacheKey;

        Aura::flushState();

        expect(User::currentTeamCacheKey($user->id))->not->toBeIn($retiredCacheKeys);
    }

    $activeCacheKey = User::currentTeamCacheKey($user->id);

    Post::count();

    expect(array_unique($retiredCacheKeys))->toHaveCount(4)
        ->and(Cache::has($activeCacheKey))->toBeTrue();

    foreach ($retiredCacheKeys as $retiredCacheKey) {
        expect(Cache::has($retiredCacheKey))->toBeFalse();
    }
});

it('clears current team and team list caches for affected users when deleting a team', function () {
    $user = createSuperAdmin();
    $firstTeam = Team::find($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Deleted Cache Team',
        'user_id' => $user->id,
    ]);
    $otherUser = User::factory()->create([
        'current_team_id' => $secondTeam->id,
    ]);

    // Attach-don't-mint: Memberships in every team point at the shared global
    // admin role (team_id = null), scoped to the team via the pivot.
    $globalAdmin = globalAdminRole();

    $firstTeam->users()->attach($otherUser->id, ['role_id' => $globalAdmin->id]);
    $secondTeam->users()->attach($otherUser->id, ['role_id' => $globalAdmin->id]);

    $firstTeamPost = createPost([
        'title' => 'Remaining Team Post',
        'team_id' => $firstTeam->id,
        'user_id' => $user->id,
    ]);
    $secondTeamPost = createPost([
        'title' => 'Deleted Team Post',
        'team_id' => $secondTeam->id,
        'user_id' => $user->id,
    ]);

    $user->refresh();
    $this->actingAs($user);
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();

    $otherUser->refresh();
    $this->actingAs($otherUser);
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();

    $userTeamsCacheKey = User::teamListCacheKey($user->id, $user->getConnection());
    $otherUserTeamsCacheKey = User::teamListCacheKey($otherUser->id, $otherUser->getConnection());

    Cache::put($userTeamsCacheKey, 'stale-user-teams');
    Cache::put($otherUserTeamsCacheKey, 'stale-other-user-teams');

    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeTrue();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeTrue();
    expect(Cache::has($userTeamsCacheKey))->toBeTrue();
    expect(Cache::has($otherUserTeamsCacheKey))->toBeTrue();

    $this->actingAs($user);
    $secondTeam->delete();

    $reassignedUser = User::withoutGlobalScopes()->find($user->id);
    $reassignedOtherUser = User::withoutGlobalScopes()->find($otherUser->id);

    expect($reassignedUser->current_team_id)->toBe($firstTeam->id);
    expect($reassignedOtherUser->current_team_id)->toBe($firstTeam->id);
    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeFalse();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeFalse();
    expect(Cache::has($userTeamsCacheKey))->toBeFalse();
    expect(Cache::has($otherUserTeamsCacheKey))->toBeFalse();

    $this->actingAs($reassignedUser);
    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();

    $this->actingAs($reassignedOtherUser);
    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();
});

it('caches a missing current team until a later model assignment invalidates it', function () {
    $user = User::factory()->create([
        'current_team_id' => null,
    ]);
    $this->actingAs($user);

    $cacheKey = User::currentTeamCacheKey($user->id);
    $currentTeamQueries = 0;

    DB::listen(function ($query) use (&$currentTeamQueries) {
        if (str_contains($query->sql, 'current_team_id') && str_contains($query->sql, 'from "users"')) {
            $currentTeamQueries++;
        }
    });

    expect(Post::count())->toBe(0);
    expect(Post::count())->toBe(0);
    expect(Cache::has($cacheKey))->toBeTrue()
        ->and($currentTeamQueries)->toBe(1);

    $team = Team::create([
        'name' => 'Later Assigned Team',
        'user_id' => $user->id,
    ]);
    $post = createPost([
        'title' => 'Later Assigned Team Post',
        'team_id' => $team->id,
        'user_id' => $user->id,
    ]);

    expect($user->fresh()->current_team_id)->toBe($team->id);
    expect(Post::whereKey($post->id)->exists())->toBeTrue();
    expect(Cache::get(User::currentTeamCacheKey($user->id)))->toBe($team->id);
});

it('prevents a cold read from republishing stale null or non-null state after invalidation', function (string $transition) {
    $owner = User::factory()->create();
    $teamA = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $teamB = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $startsNull = $transition === 'null-to-team';
    $endsNull = $transition === 'team-to-null';
    $user = User::factory()->create([
        'current_team_id' => $startsNull ? null : $teamA->id,
    ]);
    $postA = createPost(['title' => 'Race A', 'team_id' => $teamA->id, 'user_id' => $owner->id]);
    $postB = createPost(['title' => 'Race B', 'team_id' => $teamB->id, 'user_id' => $owner->id]);
    $targetTeamId = $endsNull ? null : $teamB->id;

    $this->actingAs($user);
    Aura::flushState();
    $oldCacheKey = User::currentTeamCacheKey($user->id);
    Cache::forget($oldCacheKey);
    $interleaved = false;

    DB::listen(function ($query) use (&$interleaved, $targetTeamId, $user): void {
        if ($interleaved
            || ! str_contains($query->sql, 'current_team_id')
            || ! str_contains($query->sql, 'from "users"')) {
            return;
        }

        $interleaved = true;
        DB::table('users')->where('id', $user->id)->update(['current_team_id' => $targetTeamId]);
        User::clearCurrentTeamCache($user->id, $user->getConnection());
    });

    Post::count();

    $newCacheKey = User::currentTeamCacheKey($user->id);

    expect($interleaved)->toBeTrue()
        ->and($newCacheKey)->not->toBe($oldCacheKey)
        ->and(Cache::has($oldCacheKey))->toBeTrue();

    $this->travel(2)->hours();

    expect(Cache::has($oldCacheKey))->toBeFalse();

    Aura::flushState();

    if ($endsNull) {
        expect(Post::count())->toBe(0)
            ->and(Cache::get($newCacheKey))->toBeFalse();
    } else {
        expect(Post::whereKey($postA->id)->exists())->toBeFalse()
            ->and(Post::whereKey($postB->id)->exists())->toBeTrue()
            ->and(Cache::get($newCacheKey))->toBe($teamB->id);
    }
})->with([
    'null to team' => 'null-to-team',
    'team to team' => 'team-to-team',
    'team to null after delete-like invalidation' => 'team-to-null',
]);

it('keeps a request-local team snapshot and resets it at a worker boundary', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Team B', 'team_id' => $teamB->id]);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    DB::table('users')->where('id', $user->id)->update(['current_team_id' => $teamB->id]);
    Cache::forget(User::currentTeamCacheKey($user->id));

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    Aura::flushState();

    expect(Post::whereKey($postA->id)->exists())->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue();
});

it('resets the current-team snapshot after a queue job is processed', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Queue Team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Queue Team B', 'team_id' => $teamB->id]);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    DB::table('users')->where('id', $user->id)->update(['current_team_id' => $teamB->id]);
    Cache::forget(User::currentTeamCacheKey($user->id));

    Event::dispatch(new JobProcessed('sync', Mockery::mock(Job::class), null));

    expect(Post::whereKey($postA->id)->exists())->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue();
});

it('resets the authentication guard before every real queue job boundary', function () {
    $user = createSuperAdmin();

    createPost([
        'title' => 'Authenticated worker row',
        'team_id' => $user->current_team_id,
        'user_id' => $user->id,
    ]);

    Auth::logout();
    Auth::forgetGuards();

    Queue::push(new AuthenticateQueueWorkerJob($user->id));
    expect(Auth::id())->toBeNull();

    Queue::push(new ObserveGuestQueueWorkerJob);

    expect(ObserveGuestQueueWorkerJob::$authenticatedUserId)->toBeNull()
        ->and(ObserveGuestQueueWorkerJob::$visiblePostCount)->toBe(0);
});

it('preserves an authenticated caller around a synchronous queue boundary', function () {
    $user = createSuperAdmin();

    Queue::push(new ObserveGuestQueueWorkerJob);

    expect(ObserveGuestQueueWorkerJob::$authenticatedUserId)->toBe($user->id)
        ->and(Auth::id())->toBe($user->id);
});

it('never publishes an uncommitted current team and restores scope state after rollback', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Rollback team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Rollback team B', 'team_id' => $teamB->id]);
    $cacheKey = User::currentTeamCacheKey($user->id);

    Aura::flushState();
    Cache::forget($cacheKey);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);

    DB::beginTransaction();

    try {
        $user->forceFill(['current_team_id' => $teamB->id])->save();

        $visibleInsideTransaction = Post::whereKey($postB->id)->exists();
        $sharedCacheInsideTransaction = Cache::get($cacheKey);
    } finally {
        DB::rollBack();
    }

    expect($visibleInsideTransaction)->toBeTrue()
        ->and($sharedCacheInsideTransaction)->toBe($teamA->id)
        ->and(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);
});

it('keeps a cold shared cache empty before commit and invalidates process state after commit', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postB = createPost(['title' => 'Committed team B', 'team_id' => $teamB->id]);
    $cacheKey = User::currentTeamCacheKey($user->id);

    Aura::flushState();
    Cache::forget($cacheKey);

    DB::beginTransaction();
    $user->forceFill(['current_team_id' => $teamB->id])->save();

    $visibleBeforeCommit = Post::whereKey($postB->id)->exists();
    $cacheWasPublishedBeforeCommit = Cache::has($cacheKey);

    DB::commit();

    expect($visibleBeforeCommit)->toBeTrue()
        ->and($cacheWasPublishedBeforeCommit)->toBeFalse()
        ->and(Cache::has($cacheKey))->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue()
        ->and(Cache::get(User::currentTeamCacheKey($user->id)))->toBe($teamB->id)
        ->and($teamA->id)->not->toBe($teamB->id);
});

it('survives an inner commit followed by outer rollback and a later committed switch', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Nested rollback team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Nested rollback team B', 'team_id' => $teamB->id]);
    $cacheKey = User::currentTeamCacheKey($user->id);

    Aura::flushState();
    Cache::forget($cacheKey);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);

    DB::beginTransaction();
    DB::beginTransaction();

    $user->forceFill(['current_team_id' => $teamB->id])->save();
    DB::commit();

    expect(Post::whereKey($postB->id)->exists())->toBeTrue()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);

    DB::rollBack();

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);

    DB::beginTransaction();
    User::withoutGlobalScopes()
        ->findOrFail($user->id)
        ->forceFill(['current_team_id' => $teamB->id])
        ->save();
    DB::commit();

    expect(Cache::has($cacheKey))->toBeFalse()
        ->and(Post::whereKey($postA->id)->exists())->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue()
        ->and(Cache::get(User::currentTeamCacheKey($user->id)))->toBe($teamB->id);
});

it('uses the write pdo for a cold current-team lookup after epoch rotation', function () {
    $connection = currentTeamTenantConnection();
    $user = seedCurrentTeamConnection($connection, 980000, 980010, 980011, 'Split PDO');
    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, current_team_id INTEGER NULL)');
    $readPdo->exec('INSERT INTO users (id, current_team_id) VALUES (980000, 980010)');

    $connection->table('users')->where('id', $user->getKey())->update([
        'current_team_id' => 980011,
        'global_admin' => true,
    ]);
    $user->forceFill(['global_admin' => true]);
    $connection->setReadPdo($readPdo);
    Auth::setUser($user);
    User::rotateCurrentTeamCacheEpoch($user->getKey(), $connection);
    Aura::flushState();

    expect(Post::on($connection->getName())
        ->withoutGlobalScope(ScopedScope::class)
        ->useWritePdo()
        ->pluck('title')
        ->all())
        ->toBe(['Split PDO Other'])
        ->and(Cache::get(User::currentTeamCacheKey($user->getKey(), $connection)))
        ->toBe(980011);
});

it('defaults ordinary resource writes from the authoritative current team', function () {
    $connection = currentTeamTenantConnection();
    $user = seedCurrentTeamConnection($connection, 980500, 980510, 980511, 'Writer Default');

    $connection->table('users')->where('id', $user->getKey())->update([
        'current_team_id' => 980511,
    ]);
    User::rotateCurrentTeamCacheEpoch($user->getKey(), $connection);
    Aura::flushState();
    Auth::setUser($user);

    $post = Post::on($connection->getName())->withoutGlobalScopes()->create([
        'title' => 'Writer Default Resource',
    ]);

    expect($user->getAttribute('current_team_id'))->toBe(980510)
        ->and($post->getAttribute('team_id'))->toBe(980511)
        ->and($post->getAttribute('user_id'))->toBe($user->getKey());
});

it('hydrates Aura authentication identities from the writer connection', function () {
    $connection = currentTeamTenantConnection();
    seedCurrentTeamConnection($connection, 981000, 981010, 981011, 'Writer Auth');
    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, current_team_id INTEGER NULL, global_admin INTEGER NOT NULL DEFAULT 0)');
    $readPdo->exec('CREATE TABLE meta (id INTEGER PRIMARY KEY, metable_type TEXT, metable_id INTEGER, key TEXT NULL, value TEXT NULL)');
    $readPdo->exec("INSERT INTO users (id, name, email, password, current_team_id, global_admin) VALUES (981000, 'Stale Auth', 'stale-auth@example.test', 'password', 981010, 1)");

    $connection->table('users')->where('id', 981000)->update([
        'current_team_id' => 981011,
        'global_admin' => false,
    ]);
    $connection->setReadPdo($readPdo);

    $provider = new AuraEloquentUserProvider(app('hash'), CurrentTeamTenantAuthUser::class);
    $authenticatedUser = $provider->retrieveById(981000);

    expect($authenticatedUser)->toBeInstanceOf(CurrentTeamTenantAuthUser::class)
        ->and($authenticatedUser?->getAttribute('current_team_id'))->toBe(981011)
        ->and($authenticatedUser?->getAttribute('global_admin'))->toBeFalse();
});

it('keys current-team authorization by model primary key for custom auth identifiers', function () {
    $connection = currentTeamTenantConnection();
    $user = seedCurrentTeamConnection($connection, 981100, 981110, 981111, 'Email Identifier');
    $authenticatedUser = EmailIdentifiedCurrentTeamUser::on($connection->getName())
        ->withoutGlobalScopes()
        ->findOrFail($user->getKey());

    expect($authenticatedUser->getAuthIdentifier())->toBe($user->getAttribute('email'))
        ->and($authenticatedUser->currentTeamIdForAuthorization())->toBe(981110)
        ->and(Cache::get(User::currentTeamCacheKey($user->getKey(), $connection)))->toBe(981110);

    Auth::setUser($authenticatedUser);

    $team = Team::on($connection->getName())->create([
        'name' => 'Primary Key Owned Team',
    ]);

    expect($team->getAttribute('user_id'))->toBe($user->getKey());
});

it('uses writer state when revoked global and team role authority remains stale on the reader', function () {
    $connection = currentTeamTenantConnection();
    $writer = seedRoleIsolationConnection($connection, 'Writer Revoked', false);
    $userId = $writer['user']->getKey();
    $writerTeamId = $writer['team']->getKey();
    $writerRoleId = $writer['role']->getKey();
    $staleTeamId = $writerTeamId + 1;
    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, current_team_id INTEGER NULL, global_admin INTEGER NOT NULL DEFAULT 0)');
    $readPdo->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, description TEXT NULL, super_admin INTEGER NOT NULL DEFAULT 0, permissions TEXT NULL, user_id INTEGER NULL, team_id INTEGER NULL, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec('CREATE TABLE user_role (team_id INTEGER, user_id INTEGER, role_id INTEGER, created_at TEXT NULL, updated_at TEXT NULL)');
    $readPdo->exec("INSERT INTO users (id, current_team_id, global_admin) VALUES ({$userId}, {$staleTeamId}, 1)");
    $readPdo->exec("INSERT INTO roles (id, name, slug, super_admin, permissions, team_id) VALUES ({$writerRoleId}, 'Stale Admin', 'shared-role', 1, '[]', NULL)");
    $readPdo->exec("INSERT INTO user_role (team_id, user_id, role_id) VALUES ({$staleTeamId}, {$userId}, {$writerRoleId})");

    $connection->setReadPdo($readPdo);
    $staleActor = $writer['user'];
    $staleActor->forceFill([
        'current_team_id' => $staleTeamId,
        'global_admin' => true,
    ]);
    Auth::setUser($staleActor);

    expect($staleActor->isAuraGlobalAdmin())->toBeFalse()
        ->and($staleActor->isSuperAdmin())->toBeFalse()
        ->and(Role::currentTeamIdForResolution())->toBe($writerTeamId);

    $connection->beginTransaction();
    $connection->table('users')->where('id', $userId)->update([
        'current_team_id' => $staleTeamId,
    ]);

    expect(Role::currentTeamIdForResolution())->toBe($staleTeamId);

    $connection->rollBack();

    expect(Role::currentTeamIdForResolution())->toBe($writerTeamId);
});

it('authorizes and queries the same actor-connected User model in global search', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Search User');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Search User');

    $defaultConnection->table('users')->where('id', $default['member']->getKey())->update([
        'name' => 'Default Connection User Needle',
    ]);
    $tenantConnection->table('users')->where('id', $tenant['member']->getKey())->update([
        'name' => 'Tenant Connection User Needle',
    ]);

    Aura::fake();
    Auth::setUser($tenant['global_admin']);

    $globalSearch = new GlobalSearch;
    $globalSearch->search = 'Connection User Needle';

    expect($globalSearch->getSearchResultsProperty()->flatten(1)->pluck('name')->all())
        ->toBe(['Tenant Connection User Needle'])
        ->and($default['member']->getKey())->toBe($tenant['member']->getKey());
});

it('fails closed for failover cache stores before a recovered primary can revive stale epochs', function () {
    $originalCache = Cache::getFacadeRoot();

    config([
        'cache.stores.current_team_primary' => ['driver' => 'array'],
        'cache.stores.current_team_fallback' => ['driver' => 'array'],
        'cache.stores.current_team_failover' => [
            'driver' => 'failover',
            'stores' => ['current_team_primary', 'current_team_fallback'],
        ],
    ]);
    $cacheManager = app('cache');
    $failoverCache = $cacheManager->store('current_team_failover');
    $primaryCache = $cacheManager->store('current_team_primary');
    $fallbackCache = $cacheManager->store('current_team_fallback');
    $epochKey = User::connectionScopedCacheKey(
        'current_team_generation_user_980100',
        DB::connection(),
    );
    $primaryCache->forever($epochKey, 'stale-primary-epoch');
    $fallbackCache->forever($epochKey, 'authoritative-fallback-epoch');

    try {
        Cache::swap($failoverCache);

        expect(fn () => User::currentTeamCacheEpoch(980100))
            ->toThrow(LogicException::class, 'Failover cache stores are not supported for current-team cache epochs.');

        expect(fn () => User::rotateCurrentTeamCacheEpoch(980100))
            ->toThrow(LogicException::class, 'Failover cache stores are not supported for current-team cache epochs.')
            ->and($primaryCache->get($epochKey))->toBe('stale-primary-epoch')
            ->and($fallbackCache->get($epochKey))->toBe('authoritative-fallback-epoch');
    } finally {
        Cache::swap($originalCache);
    }
});

it('keeps non-persisting cache epochs process-stable and team scope memoization bounded', function () {
    $originalCache = Cache::getFacadeRoot();
    $connection = currentTeamTenantConnection();
    $user = seedCurrentTeamConnection($connection, 980200, 980210, 980211, 'Null Store');

    try {
        Cache::swap(new CacheRepository(new NullStore));
        Aura::flushState();
        Auth::setUser($user);

        $cacheKeys = [];

        for ($lookup = 0; $lookup < 1000; $lookup++) {
            $cacheKeys[] = User::currentTeamCacheKey($user->getKey(), $connection);
            Post::on($connection->getName())->useWritePdo()->count();
        }

        $teamScopeState = (new ReflectionClass(TeamScope::class))
            ->getStaticPropertyValue('currentTeamIds');
        $stableCacheKey = $cacheKeys[0];

        expect(array_values(array_unique($cacheKeys)))->toHaveCount(1)
            ->and($teamScopeState)->toHaveCount(1);

        Aura::flushState();

        expect(User::currentTeamCacheKey($user->getKey(), $connection))
            ->not->toBe($stableCacheKey);
    } finally {
        Cache::swap($originalCache);
    }
});

it('rejects direct cross-connection resource deletes before model events or cleanup', function (string $deleteMethod) {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Direct Delete');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Direct Delete');
    $deletingEventFired = false;

    Event::listen('eloquent.deleting: '.Team::class, function () use (&$deletingEventFired): void {
        $deletingEventFired = true;
    });
    Auth::setUser($default['global_admin']);

    expect(fn () => $tenant['team']->{$deleteMethod}())
        ->toThrow(LogicException::class, 'Authenticated actors cannot delete resources on another database connection.')
        ->and($deletingEventFired)->toBeFalse()
        ->and($tenantConnection->table('teams')->where('id', $tenant['team']->getKey())->exists())->toBeTrue()
        ->and($tenantConnection->table('user_role')->where('team_id', $tenant['team']->getKey())->exists())->toBeTrue();
})->with([
    'delete' => 'delete',
    'quiet delete' => 'deleteQuietly',
    'force delete' => 'forceDelete',
    'quiet force delete' => 'forceDeleteQuietly',
]);

it('allows direct same-connection resource deletes', function (string $deleteMethod) {
    $connection = currentTeamTenantConnection();
    $tenant = seedTeamListConnection($connection, 'Same Connection Delete');

    Auth::setUser($tenant['global_admin']);

    expect($tenant['team']->{$deleteMethod}())->toBeTrue();

    $teamQuery = $connection->table('teams')->where('id', $tenant['team']->getKey());

    if (str_starts_with($deleteMethod, 'force')) {
        expect($teamQuery->exists())->toBeFalse();
    } else {
        expect($teamQuery->whereNotNull('deleted_at')->exists())->toBeTrue();
    }
})->with([
    'delete' => 'delete',
    'quiet delete' => 'deleteQuietly',
    'force delete' => 'forceDelete',
    'quiet force delete' => 'forceDeleteQuietly',
]);

it('keeps unauthenticated internal cleanup bound to the resource connection', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamDeletionConnection($defaultConnection, 'Default Internal Cleanup');
    $tenant = seedTeamDeletionConnection($tenantConnection, 'Tenant Internal Cleanup');
    $readPdo = new PDO('sqlite::memory:');
    $readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $readPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, current_team_id INTEGER NULL)');
    $readPdo->exec('CREATE TABLE user_role (team_id INTEGER, user_id INTEGER, role_id INTEGER)');
    $tenantConnection->setReadPdo($readPdo);

    Auth::forgetUser();

    expect($tenant['team']->delete())->toBeTrue()
        ->and($tenantConnection->table('teams')->useWritePdo()->where('id', $tenant['team']->getKey())->whereNotNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('users')->useWritePdo()->where('id', $tenant['user']->getKey())->value('current_team_id'))
        ->toBe($tenant['remaining_team_id'])
        ->and($tenantConnection->table('user_role')->useWritePdo()->where('team_id', $tenant['team']->getKey())->exists())
        ->toBeFalse()
        ->and($defaultConnection->table('teams')->useWritePdo()->where('id', $default['team']->getKey())->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('user_role')->useWritePdo()->where('team_id', $default['team']->getKey())->exists())
        ->toBeTrue();
});

it('rejects a deleting listener that rebinds the model before physical deletion', function (string $deleteMethod, string $beforeEvent, string $afterEvent) {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Listener Delete');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Listener Delete');
    $events = [];

    Event::listen("eloquent.{$beforeEvent}: ".Team::class, function (Team $team) use (&$events, $beforeEvent, $tenantConnection): void {
        $events[] = $beforeEvent;
        $team->setConnection($tenantConnection->getName());
    });
    Event::listen("eloquent.{$afterEvent}: ".Team::class, function () use (&$events, $afterEvent): void {
        $events[] = $afterEvent;
    });
    Auth::setUser($default['global_admin']);

    expect(fn () => $default['team']->{$deleteMethod}())
        ->toThrow(LogicException::class, 'A resource connection cannot change during deletion.')
        ->and($events)->toBe([$beforeEvent])
        ->and($defaultConnection->table('teams')->where('id', $default['team']->getKey())->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('teams')->where('id', $tenant['team']->getKey())->whereNull('deleted_at')->exists())
        ->toBeTrue()
        ->and($defaultConnection->table('user_role')->where('team_id', $default['team']->getKey())->exists())
        ->toBeTrue()
        ->and($tenantConnection->table('user_role')->where('team_id', $tenant['team']->getKey())->exists())
        ->toBeTrue();
})->with([
    'delete' => ['delete', 'deleting', 'deleted'],
    'force delete' => ['forceDelete', 'forceDeleting', 'forceDeleted'],
]);

it('preserves successful delete lifecycle ordering on the bound connection', function (string $deleteMethod, array $expectedEvents) {
    $connection = DB::connection();
    $data = seedTeamListConnection($connection, 'Delete Event Order');
    $events = [];

    foreach (['forceDeleting', 'deleting', 'deleted', 'forceDeleted'] as $event) {
        Event::listen("eloquent.{$event}: ".Team::class, function () use (&$events, $event): void {
            $events[] = $event;
        });
    }

    Auth::setUser($data['global_admin']);

    expect($data['team']->{$deleteMethod}())->toBeTrue()
        ->and($events)->toBe($expectedEvents);
})->with([
    'delete' => ['delete', ['deleting', 'deleted']],
    'force delete' => ['forceDelete', ['forceDeleting', 'deleting', 'deleted', 'forceDeleted']],
]);

it('keeps quiet deletes on their original connection without dispatching listeners', function (string $deleteMethod) {
    $defaultConnection = DB::connection();
    $tenantConnection = currentTeamTenantConnection();
    $default = seedTeamListConnection($defaultConnection, 'Default Quiet Delete');
    $tenant = seedTeamListConnection($tenantConnection, 'Tenant Quiet Delete');
    $events = [];

    Event::listen('eloquent.deleting: '.Team::class, function (Team $team) use (&$events, $tenantConnection): void {
        $events[] = 'deleting';
        $team->setConnection($tenantConnection->getName());
    });
    Auth::setUser($default['global_admin']);

    expect($default['team']->{$deleteMethod}())->toBeTrue()
        ->and($events)->toBeEmpty()
        ->and($tenantConnection->table('teams')->where('id', $tenant['team']->getKey())->whereNull('deleted_at')->exists())
        ->toBeTrue();

    $defaultTeamQuery = $defaultConnection->table('teams')->where('id', $default['team']->getKey());

    if ($deleteMethod === 'forceDeleteQuietly') {
        expect($defaultTeamQuery->exists())->toBeFalse();
    } else {
        expect($defaultTeamQuery->whereNotNull('deleted_at')->exists())->toBeTrue();
    }
})->with([
    'quiet delete' => 'deleteQuietly',
    'quiet force delete' => 'forceDeleteQuietly',
]);
