<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Roles as RolesField;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Livewire\UserTeams;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

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

    public function resolvedRoleForTest(string|int $userId, int $teamId): ?Role
    {
        return $this->resolvedRoleForUser($userId, $teamId);
    }

    public function userForTest(): User
    {
        return $this->user();
    }
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

    expect($defaultGlobalTeams->pluck('name')->all())->toBe(['Default Team'])
        ->and($defaultMemberTeams->pluck('name')->all())->toBe(['Default Team'])
        ->and($tenantGlobalTeams->pluck('name')->all())->toBe(['Tenant Team'])
        ->and($tenantMemberTeams->pluck('name')->all())->toBe(['Tenant Team'])
        ->and($tenantGlobalTeams->first()->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($tenantMemberTeams->first()->getConnection()->getName())->toBe($tenantConnection->getName())
        ->and($default['member']->getOption('connection-check'))->toBe(['Default User Option'])
        ->and($tenant['member']->getOption('connection-check'))->toBe(['Tenant User Option'])
        ->and($default['team']->getOption('connection-check'))->toBe(['Default Team Option'])
        ->and($tenant['team']->getOption('connection-check'))->toBe(['Tenant Team Option'])
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
        ->and(Cache::get($tenantCacheKey))->toBe(920021);
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
    expect(Cache::get($cacheKey))->toBe($secondTeam->id);
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
    expect(Cache::get($cacheKey))->toBe($team->id);
});

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
        ->and(Cache::get($cacheKey))->toBe($teamB->id)
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
        ->and(Cache::get($cacheKey))->toBe($teamB->id);
});
