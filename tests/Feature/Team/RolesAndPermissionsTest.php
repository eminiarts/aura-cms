<?php

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team tests require the teams schema.');
    }
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Roles Table Structure', function () {
    it('has its custom table', function () {
        $this->assertTrue(Schema::hasTable('roles'));
    });

    it('has all expected columns', function () {
        $expectedColumns = ['id', 'team_id', 'name', 'slug', 'description', 'super_admin', 'permissions', 'created_at', 'updated_at'];
        $actualColumns = Schema::getColumnListing('roles');

        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $actualColumns, "The roles table is missing the {$column} column.");
        }
    });
});

describe('Permissions Table Structure', function () {
    it('has its custom table', function () {
        $this->assertTrue(Schema::hasTable('permissions'));
    });

    it('has all expected columns', function () {
        $expectedColumns = ['id', 'team_id', 'name', 'slug', 'created_at', 'updated_at'];
        $actualColumns = Schema::getColumnListing('permissions');

        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $actualColumns, "The permissions table is missing the {$column} column.");
        }
    });
});

describe('Team Membership Creation', function () {
    // Attach-don't-mint: creating a team no longer mints a per-team admin role;
    // the creator is attached to the shared global `admin` role (team_id = null).
    it('attaches the creator to the global admin role when team is created', function () {
        $team = Team::factory()->create();

        $globalAdmin = Role::withoutGlobalScopes()
            ->whereNull('team_id')
            ->where('slug', 'admin')
            ->first();

        expect($globalAdmin)->not->toBeNull();
        expect($globalAdmin->super_admin)->toBeTrue();

        $this->assertDatabaseHas('user_role', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role_id' => $globalAdmin->id,
        ]);
    });

    it('does not mint a per-team admin role', function () {
        $team = Team::factory()->create();

        $perTeamRole = Role::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->exists();

        expect($perTeamRole)->toBeFalse();
    });

    it('makes the creator a super admin of the new team via the global role', function () {
        $team = Team::factory()->create();

        $this->user->update(['current_team_id' => $team->id]);
        $this->user->refresh();

        expect($this->user->isSuperAdmin())->toBeTrue();
    });
});

describe('Team Permission Creation', function () {
    it('dispatches permission generation job when team is created', function () {
        // The GenerateAllResourcePermissions job is dispatched on team creation
        // We test that the job runs correctly when dispatched directly
        GenerateAllResourcePermissions::dispatchSync($this->user->currentTeam->id);

        $this->assertDatabaseHas('permissions', ['team_id' => $this->user->currentTeam->id]);
        $this->assertGreaterThan(0, Permission::where('team_id', $this->user->currentTeam->id)->count());
    });

    it('excludes team resource from permissions', function () {
        GenerateAllResourcePermissions::dispatchSync($this->user->currentTeam->id);

        $this->assertDatabaseMissing('permissions', [
            'name' => 'Create Teams',
        ]);
    });

    it('creates standard CRUD permissions for resources', function () {
        GenerateAllResourcePermissions::dispatchSync($this->user->currentTeam->id);

        $expectedPermissions = ['View', 'View Any', 'Create', 'Update', 'Restore', 'Delete', 'Force Delete'];

        foreach ($expectedPermissions as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => "{$permission} Users",
            ]);
        }
    });

    it('creates permission slugs in correct format', function () {
        GenerateAllResourcePermissions::dispatchSync($this->user->currentTeam->id);

        $permission = Permission::where('team_id', $this->user->currentTeam->id)->first();

        // Slugs should be lowercase with dashes
        expect($permission)->not->toBeNull();
        expect($permission->slug)->toMatch('/^[a-z0-9\-]+$/');
    });
});

describe('Role Permissions Structure', function () {
    it('stores permissions as array', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'team_id' => $this->user->currentTeam->id,
            'permissions' => [
                'view-user' => true,
                'create-user' => false,
            ],
        ]);

        $role->refresh();

        expect($role->permissions)->toBeArray();
        expect($role->permissions)->toHaveKey('view-user');
        expect($role->permissions['view-user'])->toBeTrue();
    });

    it('can have empty permissions array', function () {
        $role = Role::create([
            'name' => 'Empty Permissions Role',
            'slug' => 'empty_permissions',
            'team_id' => $this->user->currentTeam->id,
            'permissions' => [],
        ]);

        $role->refresh();

        expect($role->permissions)->toBeArray();
        expect($role->permissions)->toBeEmpty();
    });
});

describe('Role Team Association', function () {
    it('a Team Role belongs to a team', function () {
        $team = Team::factory()->create();

        // A genuine Team Role (as opposed to the shared global admin) still
        // carries the team_id it was created under.
        $role = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'team_id' => $team->id,
            'permissions' => [],
        ]);

        expect($role->team_id)->toBe($team->id);
    });

    it('teams share the single global admin role rather than minting separate rows', function () {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // No per-team admin rows are minted for either team.
        expect(Role::withoutGlobalScopes()->where('team_id', $team1->id)->count())->toBe(0);
        expect(Role::withoutGlobalScopes()->where('team_id', $team2->id)->count())->toBe(0);

        // Both teams' Memberships resolve to the same shared global admin role.
        $globalAdmin = globalAdminRole();

        $this->assertDatabaseHas('user_role', ['team_id' => $team1->id, 'role_id' => $globalAdmin->id]);
        $this->assertDatabaseHas('user_role', ['team_id' => $team2->id, 'role_id' => $globalAdmin->id]);
    });
});

describe('Permission Team Association', function () {
    it('permissions belong to a team after generation', function () {
        // Generate permissions for current team
        GenerateAllResourcePermissions::dispatchSync($this->user->currentTeam->id);

        $permission = Permission::where('team_id', $this->user->currentTeam->id)->first();

        expect($permission)->not->toBeNull();
        expect($permission->team_id)->toBe($this->user->currentTeam->id);
    });

    it('different teams have separate permissions after generation', function () {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // Generate permissions for each team
        GenerateAllResourcePermissions::dispatchSync($team1->id);
        GenerateAllResourcePermissions::dispatchSync($team2->id);

        $team1Permissions = Permission::withoutGlobalScopes()->where('team_id', $team1->id)->get();
        $team2Permissions = Permission::withoutGlobalScopes()->where('team_id', $team2->id)->get();

        // Each team should have permissions
        expect($team1Permissions->count())->toBeGreaterThan(0);
        expect($team2Permissions->count())->toBeGreaterThan(0);
    });
});
