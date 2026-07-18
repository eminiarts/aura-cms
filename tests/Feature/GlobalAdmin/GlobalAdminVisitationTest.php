<?php

use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;

/**
 * Promote a fresh user to Global Admin the trusted way (direct, pipeline-bypassing
 * write). Mirrors the CLI bootstrap posture.
 */
function visitingGa(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}

/**
 * A team the acting user neither owns nor belongs to.
 */
function foreignTeam(): Team
{
    return Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
}

describe('Global Admin visitation via switchTeam', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('Visitation is a teams-on power.');
        }
    });

    it('lets a Global Admin enter a team they do not belong to, creating no Membership', function () {
        $ga = visitingGa();
        $this->actingAs($ga);
        $team = foreignTeam();

        expect($ga->switchTeam($team))->toBeTrue()
            ->and($ga->fresh()->current_team_id)->toBe($team->id);

        // Visitation must never mint a user_role row.
        expect(
            DB::table('user_role')->where('user_id', $ga->id)->where('team_id', $team->id)->exists()
        )->toBeFalse();
    });

    it('refuses a regular user switching into a non-member team (unit)', function () {
        $user = createSuperAdmin(); // super admin of their own team, not a Global Admin
        $team = foreignTeam();

        expect($user->switchTeam($team))->toBeFalse();
    });

    it('lets a Global Admin visit any team over HTTP', function () {
        $ga = visitingGa();
        $team = foreignTeam();

        $this->actingAs($ga)
            ->put(route('aura.current-team.update', ['team_id' => $team->id]))
            ->assertRedirect(route('aura.dashboard'));

        expect($ga->fresh()->current_team_id)->toBe($team->id);
    });

    it('refuses a regular user visiting a non-member team over HTTP (403)', function () {
        $user = createSuperAdmin();
        $team = foreignTeam();

        $this->actingAs($user)
            ->put(route('aura.current-team.update', ['team_id' => $team->id]))
            ->assertForbidden();

        expect($user->fresh()->current_team_id)->not->toBe($team->id);
    });
});

describe('a visiting Global Admin acts with Super Admin power', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('Visitation is a teams-on power.');
        }

        $this->ga = visitingGa();
        $this->team = foreignTeam();
        $this->actingAs($this->ga);
        $this->ga->switchTeam($this->team);
        $this->actingAs($this->ga->refresh());
    });

    it('holds no resolved roles inside the visited team (power is not role-derived)', function () {
        // The resolution seam is untouched: a visitor has no Membership, so no
        // roles resolve and isSuperAdmin() is false — the power comes purely from
        // the gate bypasses.
        expect($this->ga->isSuperAdmin())->toBeFalse()
            ->and($this->ga->isAuraGlobalAdmin())->toBeTrue()
            ->and($this->ga->cachedRoles())->toBeEmpty();
    });

    it('passes ResourcePolicy checks inside the visited team', function () {
        $policy = new ResourcePolicy;

        expect($policy->viewAny($this->ga, new Post))->toBeTrue()
            ->and($policy->create($this->ga, new Post))->toBeTrue()
            ->and($policy->update($this->ga, new Post))->toBeTrue();
    });

    it('passes TeamPolicy::update on the visited team', function () {
        expect((new TeamPolicy)->update($this->ga, $this->team))->toBeTrue();
    });
});

describe('a Global Admin with zero Memberships', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('Visitation is a teams-on power.');
        }
    });

    it('is not a Super Admin, is a Global Admin, and can still visit and administer', function () {
        $ga = visitingGa(['current_team_id' => null]); // no team, no current_team_id, no roles
        $this->actingAs($ga);

        expect($ga->isSuperAdmin())->toBeFalse()
            ->and($ga->isAuraGlobalAdmin())->toBeTrue()
            ->and($ga->current_team_id)->toBeNull();

        $team = foreignTeam();

        expect($ga->switchTeam($team))->toBeTrue()
            ->and($ga->fresh()->current_team_id)->toBe($team->id);
    });
});

describe('Teams-off mode', function () {
    beforeEach(function () {
        if (config('aura.teams')) {
            $this->markTestSkipped('Teams-off no-op assertions only.');
        }
    });

    it('grants no visitation through the flag', function () {
        $ga = visitingGa();
        $this->actingAs($ga);

        // The flag is live, but visitation is gated on teams being enabled, so a
        // stray team object never switches the pointer via the Global Admin path.
        expect($ga->isAuraGlobalAdmin())->toBeTrue()
            ->and($ga->switchTeam(new Team(['id' => 999])))->toBeFalse();
    });
});
