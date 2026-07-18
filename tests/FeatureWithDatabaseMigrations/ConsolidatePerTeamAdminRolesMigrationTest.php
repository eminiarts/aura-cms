<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Rebuild a fresh teams-on schema, matching the DatabaseMigrations pattern
    // used by MigrationOwnershipTest, so the consolidation migration runs against
    // a deterministic pre-upgrade shape (roles.team_id + user_role.team_id).
    foreach (Schema::getTableListing() as $table) {
        $table = str($table)->afterLast('.')->toString();

        if ($table !== 'migrations') {
            Schema::drop($table);
        }
    }

    config(['aura.teams' => true]);

    $install = require dirname(__DIR__, 2).'/database/migrations/create_aura_tables.php.stub';
    $install->up();
});

function runConsolidationMigration(): void
{
    $migration = require dirname(__DIR__, 2).'/database/migrations/consolidate_per_team_admin_roles.php.stub';
    $migration->up();
}

function insertPerTeamRole(int $teamId, array $overrides = []): int
{
    return (int) DB::table('roles')->insertGetId(array_merge([
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => 'Admin can perform everything.',
        'super_admin' => true,
        'permissions' => json_encode([]),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

it('consolidates an untouched per-team admin row into the global admin role', function () {
    // Build the pre-upgrade state directly, bypassing the attach-don't-mint
    // hooks: a team whose creator holds a minted per-team admin row.
    $team = Team::factory()->createQuietly();
    $user = User::factory()->create();

    $perTeamRoleId = insertPerTeamRole($team->id);

    DB::table('user_role')->insert([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role_id' => $perTeamRoleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runConsolidationMigration();

    // The per-team admin row is gone; a single global admin row exists.
    expect(DB::table('roles')->where('id', $perTeamRoleId)->exists())->toBeFalse();

    $globalAdmin = DB::table('roles')->whereNull('team_id')->where('slug', 'admin')->first();
    expect($globalAdmin)->not->toBeNull();
    expect((bool) $globalAdmin->super_admin)->toBeTrue();

    // The Membership is remapped to the global role, still scoped to the team.
    $membership = DB::table('user_role')->where('user_id', $user->id)->where('team_id', $team->id)->first();
    expect((int) $membership->role_id)->toBe((int) $globalAdmin->id);
});

it('leaves a customized admin row in place as a Shadow', function () {
    $team = Team::factory()->createQuietly();
    $user = User::factory()->create();

    // Customized: a non-empty permission set marks it as intentionally edited.
    $shadowRoleId = insertPerTeamRole($team->id, [
        'permissions' => json_encode(['view-user' => true]),
    ]);

    DB::table('user_role')->insert([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role_id' => $shadowRoleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runConsolidationMigration();

    // The customized row survives as a Team Role (Shadow) and keeps its Membership.
    expect(DB::table('roles')->where('id', $shadowRoleId)->whereNotNull('team_id')->exists())->toBeTrue();

    $membership = DB::table('user_role')->where('user_id', $user->id)->where('team_id', $team->id)->first();
    expect((int) $membership->role_id)->toBe($shadowRoleId);
});

it('consolidates untouched rows while leaving customized rows as Shadows in one pass', function () {
    $teamA = Team::factory()->createQuietly();
    $teamB = Team::factory()->createQuietly();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $untouchedId = insertPerTeamRole($teamA->id);
    $customizedId = insertPerTeamRole($teamB->id, [
        'name' => 'Team Admin',
        'permissions' => json_encode(['view-user' => true]),
    ]);

    DB::table('user_role')->insert([
        ['team_id' => $teamA->id, 'user_id' => $userA->id, 'role_id' => $untouchedId, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $teamB->id, 'user_id' => $userB->id, 'role_id' => $customizedId, 'created_at' => now(), 'updated_at' => now()],
    ]);

    runConsolidationMigration();

    $globalAdmin = DB::table('roles')->whereNull('team_id')->where('slug', 'admin')->first();

    // Untouched consolidated.
    expect(DB::table('roles')->where('id', $untouchedId)->exists())->toBeFalse();
    expect((int) DB::table('user_role')->where('user_id', $userA->id)->value('role_id'))->toBe((int) $globalAdmin->id);

    // Customized preserved.
    expect(DB::table('roles')->where('id', $customizedId)->exists())->toBeTrue();
    expect((int) DB::table('user_role')->where('user_id', $userB->id)->value('role_id'))->toBe($customizedId);
});

it('is a strict no-op on a fresh install with no per-team admin rows', function () {
    $rolesBefore = DB::table('roles')->count();
    $membershipsBefore = DB::table('user_role')->count();

    runConsolidationMigration();

    expect(DB::table('roles')->count())->toBe($rolesBefore);
    expect(DB::table('user_role')->count())->toBe($membershipsBefore);
});
