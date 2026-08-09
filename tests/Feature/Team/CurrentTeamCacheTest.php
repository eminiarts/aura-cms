<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
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
        $table->boolean('super_admin')->default(false);
        $table->json('permissions')->nullable();
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

    Cache::put('user.'.$user->id.'.teams', 'stale-user-teams');
    Cache::put('user.'.$otherUser->id.'.teams', 'stale-other-user-teams');

    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeTrue();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeTrue();
    expect(Cache::has('user.'.$user->id.'.teams'))->toBeTrue();
    expect(Cache::has('user.'.$otherUser->id.'.teams'))->toBeTrue();

    $this->actingAs($user);
    $secondTeam->delete();

    $reassignedUser = User::withoutGlobalScopes()->find($user->id);
    $reassignedOtherUser = User::withoutGlobalScopes()->find($otherUser->id);

    expect($reassignedUser->current_team_id)->toBe($firstTeam->id);
    expect($reassignedOtherUser->current_team_id)->toBe($firstTeam->id);
    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeFalse();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeFalse();
    expect(Cache::has('user.'.$user->id.'.teams'))->toBeFalse();
    expect(Cache::has('user.'.$otherUser->id.'.teams'))->toBeFalse();

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
