<?php

use Aura\Base\Livewire\UserTeams;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

/**
 * A Team Role owned by the given team, unscoped so it can be created regardless
 * of the acting user's current team.
 */
function membershipRole(int $teamId, array $attributes = []): Role
{
    return Role::withoutGlobalScopes()->create(array_merge([
        'type' => 'Role',
        'name' => 'Member',
        'slug' => 'member-'.$teamId.'-'.uniqid(),
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $teamId,
    ], $attributes));
}

/**
 * A Global Role (team_id = null) in the shared catalog. Written with
 * saveQuietly() so the InitialPostFields saving hook does not re-team the null
 * team_id to the current team — the same posture Role::firstOrCreateCatalogRole
 * uses.
 */
function catalogRole(string $slug, string $name, bool $superAdmin = false): Role
{
    $role = Role::withoutGlobalScopes()->newModelInstance([
        'type' => 'Role',
        'name' => $name,
        'slug' => $slug,
        'super_admin' => $superAdmin,
        'permissions' => [],
        'team_id' => null,
    ]);

    $role->saveQuietly();

    return $role;
}

/** Record a Membership: user in $team with role $roleId (pivot team_id). */
function attachMember(User $user, int $teamId, int $roleId): void
{
    $user->roles()->attach($roleId, ['team_id' => $teamId]);
}

/** The pivot row for a (user, team) Membership, unscoped. */
function membershipPivot(int $userId, int $teamId): ?object
{
    return DB::table('user_role')
        ->where('user_id', $userId)
        ->where('team_id', $teamId)
        ->first();
}

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('The Membership editor is a teams-on feature.');
    }
});

describe('Membership editor display', function () {
    it('shows each team with the role resolved through the catalog seam (shadow wins)', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team1 = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $team2 = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);

        // A Global Role 'editor' and a team1 Shadow of it with a different name.
        $globalEditor = catalogRole('editor', 'Global Editor');
        $team1Shadow = membershipRole($team1->id, ['slug' => 'editor', 'name' => 'Team1 Editor']);

        // Plain role in team2.
        $team2Role = membershipRole($team2->id, ['name' => 'Team2 Member']);

        $viewed = User::factory()->create();
        // The pivot stores the GLOBAL editor id; team1 shadows 'editor', so it
        // must display as the Shadow's name — proving resolution by slug.
        attachMember($viewed, $team1->id, $globalEditor->id);
        attachMember($viewed, $team2->id, $team2Role->id);

        $rows = collect(livewire(UserTeams::class, ['userId' => $viewed->id])->instance()->memberships);

        expect($rows)->toHaveCount(2);

        $row1 = $rows->firstWhere('team_id', $team1->id);
        $row2 = $rows->firstWhere('team_id', $team2->id);

        expect($row1['role_name'])->toBe('Team1 Editor')
            ->and($row1['role_id'])->toBe($team1Shadow->id)
            ->and($row2['role_name'])->toBe('Team2 Member');
    });
});

describe('Global Admin management', function () {
    it('attaches the viewed user to any team with a chosen role', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $role = membershipRole($team->id);
        $viewed = User::factory()->create();

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $role->id)
            ->call('attach')
            ->assertHasNoErrors();

        $pivot = membershipPivot($viewed->id, $team->id);
        expect($pivot)->not->toBeNull()
            ->and((int) $pivot->role_id)->toBe($role->id)
            ->and((int) $pivot->team_id)->toBe($team->id);
    });

    it('changes the per-team role', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $roleA = membershipRole($team->id, ['name' => 'Role A']);
        $roleB = membershipRole($team->id, ['name' => 'Role B']);
        $viewed = User::factory()->create();
        attachMember($viewed, $team->id, $roleA->id);

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('changeRole', $team->id, $roleB->id)
            ->assertHasNoErrors();

        expect((int) membershipPivot($viewed->id, $team->id)->role_id)->toBe($roleB->id);
        // Still exactly one Membership row for that team.
        expect(DB::table('user_role')->where('user_id', $viewed->id)->where('team_id', $team->id)->count())->toBe(1);
    });

    it('detaches a Membership', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $role = membershipRole($team->id);
        $viewed = User::factory()->create();
        attachMember($viewed, $team->id, $role->id);

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('detach', $team->id)
            ->assertHasNoErrors();

        expect(membershipPivot($viewed->id, $team->id))->toBeNull();
    });

    it('falls the current team back when detaching it', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $teamA = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $teamB = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $roleA = membershipRole($teamA->id);
        $roleB = membershipRole($teamB->id);

        $viewed = User::factory()->create();
        attachMember($viewed, $teamA->id, $roleA->id);
        attachMember($viewed, $teamB->id, $roleB->id);
        $viewed->forceFill(['current_team_id' => $teamA->id])->save();

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('detach', $teamA->id)
            ->assertHasNoErrors();

        // Fell back to the remaining Membership (teamB).
        expect((int) $viewed->fresh()->current_team_id)->toBe($teamB->id);
    });

    it('falls the current team back to null when no Membership remains', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $teamA = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $roleA = membershipRole($teamA->id);

        $viewed = User::factory()->create();
        attachMember($viewed, $teamA->id, $roleA->id);
        $viewed->forceFill(['current_team_id' => $teamA->id])->save();

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('detach', $teamA->id)
            ->assertHasNoErrors();

        expect($viewed->fresh()->current_team_id)->toBeNull();
    });
});

describe('Team Super Admin management (team-scoped)', function () {
    it('lets a team Super Admin manage Memberships for their own team', function () {
        $actor = createSuperAdmin();
        $teamX = $actor->currentTeam;
        $this->actingAs($actor);

        $role = membershipRole($teamX->id);
        $viewed = User::factory()->create();

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $teamX->id)
            ->set('attachRoleId', $role->id)
            ->call('attach')
            ->assertHasNoErrors();

        expect(membershipPivot($viewed->id, $teamX->id))->not->toBeNull();

        $role2 = membershipRole($teamX->id, ['name' => 'Second']);
        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('changeRole', $teamX->id, $role2->id)
            ->assertHasNoErrors();

        expect((int) membershipPivot($viewed->id, $teamX->id)->role_id)->toBe($role2->id);
    });

    it('refuses (403) an operation submitted for a team the actor does not administer, with no pivot change', function () {
        $actor = createSuperAdmin();
        $this->actingAs($actor);

        $teamY = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $roleY = membershipRole($teamY->id);
        $viewed = User::factory()->create();
        attachMember($viewed, $teamY->id, $roleY->id);

        // Detach for a foreign team → 403 and Membership intact.
        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('detach', $teamY->id)
            ->assertStatus(403);

        expect((int) membershipPivot($viewed->id, $teamY->id)->role_id)->toBe($roleY->id);

        // Change role for a foreign team → 403 and role unchanged.
        $otherRole = membershipRole($teamY->id, ['name' => 'Other']);
        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('changeRole', $teamY->id, $otherRole->id)
            ->assertStatus(403);

        expect((int) membershipPivot($viewed->id, $teamY->id)->role_id)->toBe($roleY->id);
    });

    it('marks rows for teams the actor cannot manage as read-only', function () {
        $actor = createSuperAdmin();
        $teamX = $actor->currentTeam;
        $this->actingAs($actor);

        $teamY = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $roleX = membershipRole($teamX->id);
        $roleY = membershipRole($teamY->id);

        $viewed = User::factory()->create();
        attachMember($viewed, $teamX->id, $roleX->id);
        attachMember($viewed, $teamY->id, $roleY->id);

        $rows = collect(livewire(UserTeams::class, ['userId' => $viewed->id])->instance()->memberships);

        expect($rows->firstWhere('team_id', $teamX->id)['can_manage'])->toBeTrue()
            ->and($rows->firstWhere('team_id', $teamY->id)['can_manage'])->toBeFalse();
    });
});

describe('Constraints and guards', function () {
    it('refuses attaching to a team the user already belongs to (validation, single row kept)', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $role = membershipRole($team->id);
        $viewed = User::factory()->create();
        attachMember($viewed, $team->id, $role->id);

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $role->id)
            ->call('attach')
            ->assertHasErrors('attachTeamId');

        expect(DB::table('user_role')->where('user_id', $viewed->id)->where('team_id', $team->id)->count())->toBe(1);
    });

    it('refuses a role owned by another team', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $otherTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $foreignRole = membershipRole($otherTeam->id);
        $viewed = User::factory()->create();

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $foreignRole->id)
            ->call('attach')
            ->assertStatus(403);

        expect(membershipPivot($viewed->id, $team->id))->toBeNull();
    });

    it('refuses a Global Role submitted by its hidden id when the team shadows that slug', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $globalEditor = catalogRole('editor', 'Global Editor');
        membershipRole($team->id, ['slug' => 'editor', 'name' => 'Team Editor']); // Shadow

        $viewed = User::factory()->create();

        // The shadowed Global Role's id is not in the team's assignable set.
        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $globalEditor->id)
            ->call('attach')
            ->assertStatus(403);

        expect(membershipPivot($viewed->id, $team->id))->toBeNull();
    });

    it('refuses a non-super-admin actor granting a Super Admin role (escalation guard)', function () {
        // A delegated user-manager (not a Super Admin) in the team.
        $owner = User::factory()->create();
        $team = Team::factory()->createQuietly(['user_id' => $owner->id]);

        $managerRole = membershipRole($team->id, [
            'name' => 'User Manager',
            'permissions' => ['viewAny-user' => true, 'view-user' => true, 'update-user' => true],
        ]);
        $manager = User::factory()->create(['current_team_id' => $team->id]);
        attachMember($manager, $team->id, $managerRole->id);

        $superAdminRole = catalogRole('admin-escalation', 'Escalation Admin', true);

        $viewed = User::factory()->create();
        $this->actingAs($manager);

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $superAdminRole->id)
            ->call('attach')
            ->assertStatus(403);

        expect(membershipPivot($viewed->id, $team->id))->toBeNull();
    });

    it('renders read-only and refuses operations for a regular member', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->createQuietly(['user_id' => $owner->id]);
        $memberRole = membershipRole($team->id, ['name' => 'Plain Member']);

        $actor = User::factory()->create(['current_team_id' => $team->id]);
        attachMember($actor, $team->id, $memberRole->id);

        $viewed = User::factory()->create();
        attachMember($viewed, $team->id, $memberRole->id);

        $this->actingAs($actor);

        // Read-only in the rendered data.
        $rows = collect(livewire(UserTeams::class, ['userId' => $viewed->id])->instance()->memberships);
        expect($rows->firstWhere('team_id', $team->id)['can_manage'])->toBeFalse();

        // And server-side refusal.
        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->call('detach', $team->id)
            ->assertStatus(403);

        expect(membershipPivot($viewed->id, $team->id))->not->toBeNull();
    });

    it('clears the viewed user team cache after a Membership change', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $role = membershipRole($team->id);
        $viewed = User::factory()->create();

        Cache::put('user.'.$viewed->id.'.teams', collect(['stale']), now()->addHour());

        livewire(UserTeams::class, ['userId' => $viewed->id])
            ->set('attachTeamId', $team->id)
            ->set('attachRoleId', $role->id)
            ->call('attach')
            ->assertHasNoErrors();

        expect(Cache::has('user.'.$viewed->id.'.teams'))->toBeFalse();
    });
});

describe('Teams-off guard', function () {
    it('404s the component when teams are disabled', function () {
        $teamsEnabled = config('aura.teams');
        config(['aura.teams' => false]);

        $viewed = User::factory()->create();

        try {
            livewire(UserTeams::class, ['userId' => $viewed->id])
                ->assertStatus(404);
        } finally {
            config(['aura.teams' => $teamsEnabled]);
        }
    });
});
