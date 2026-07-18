<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

require_once __DIR__.'/Support/helpers.php';

/*
|--------------------------------------------------------------------------
| Teams tab + Membership editor browser journeys (issue #56)
|--------------------------------------------------------------------------
|
| Real Chromium against the Testbench app: the Teams tab lists a user's actual
| Memberships (team + resolved role); a Global Admin changes a per-team role,
| attaches the user to another team, and detaches a Membership through the
| dedicated editor; a plain team Super Admin only manages the teams they
| administer (their own team is editable, another team's row is read-only).
|
*/

/**
 * A team owned by a throwaway user, created quietly so no creator Membership or
 * per-team admin row is minted — a clean tenant to attach the target user into.
 */
function browserQuietTeam(string $name): Team
{
    return Team::factory()->createQuietly([
        'name' => $name,
        'user_id' => User::factory()->create()->id,
    ]);
}

test('a Global Admin views both Memberships and changes, attaches, and detaches through the editor', function () {
    $teamA = browserQuietTeam('Team Alpha');
    $teamB = browserQuietTeam('Team Beta');
    $teamC = browserQuietTeam('Team Gamma');

    // Distinct Team Roles per team so resolved names are unambiguous.
    $alphaOne = browserTeamRole($teamA->id, 'Alpha One', 'alpha-one');
    $alphaTwo = browserTeamRole($teamA->id, 'Alpha Two', 'alpha-two');
    $betaOne = browserTeamRole($teamB->id, 'Beta One', 'beta-one');
    $gammaOne = browserTeamRole($teamC->id, 'Gamma One', 'gamma-one');

    // The viewed user holds a Membership in teams A and B.
    $target = User::factory()->create(['name' => 'Multi Team', 'email' => 'multi-team@example.com']);
    browserAttachMembership($target, $teamA->id, $alphaOne->id);
    browserAttachMembership($target, $teamB->id, $betaOne->id);
    $target->forceFill(['current_team_id' => $teamA->id])->save();

    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);

    $page = visit('/admin/user/'.$target->id);

    // Open the Teams tab; both Memberships render with their resolved role names.
    $page->click('[role="tab"]:has-text("Teams")')->wait(1);

    $page->assertSee('Team Alpha')
        ->assertSee('Team Beta');

    // A Global Admin manages every team, so each role renders as an editable
    // select pre-set to the user's resolved role in that team.
    $page->assertValue('[dusk="role-select-'.$teamA->id.'"]', (string) $alphaOne->id)
        ->assertValue('[dusk="role-select-'.$teamB->id.'"]', (string) $betaOne->id);

    // Change the role in team A through the per-row select.
    $page->select('[dusk="role-select-'.$teamA->id.'"]', (string) $alphaTwo->id)->wait(2);

    expect(browserMembershipExists($target->id, $alphaTwo->id, $teamA->id))->toBeTrue()
        ->and(browserMembershipExists($target->id, $alphaOne->id, $teamA->id))->toBeFalse();

    // Attach the user to a third team via the attach form (team is wire:model.live,
    // so the role options populate after the team is chosen).
    $page->select('[dusk="attach-team"]', (string) $teamC->id)->wait(2);
    $page->select('[dusk="attach-role"]', (string) $gammaOne->id)->wait(1);
    $page->click('[dusk="attach-submit"]')->wait(2);

    expect(browserMembershipExists($target->id, $gammaOne->id, $teamC->id))->toBeTrue();

    $page->assertSee('Team Gamma');

    // Detach team B; the row and the pivot both disappear.
    $page->click('[dusk="detach-'.$teamB->id.'"]')->wait(2);

    expect(browserMembershipExists($target->id, $betaOne->id, $teamB->id))->toBeFalse();

    $page->assertDontSee('Team Beta');
});

test('a team Super Admin edits their own team but sees another team read-only', function () {
    // The acting Super Admin owns team A (the team they administer).
    $admin = browserSuperAdmin('admin-password');
    $teamA = $admin->currentTeam;
    $teamB = browserQuietTeam('Team Beta');

    $alphaOne = browserTeamRole($teamA->id, 'Alpha One', 'alpha-one');
    $alphaTwo = browserTeamRole($teamA->id, 'Alpha Two', 'alpha-two');
    $betaOne = browserTeamRole($teamB->id, 'Beta One', 'beta-one');

    // The viewed user is a member of both the admin's team and a foreign team.
    $target = User::factory()->create(['name' => 'Shared Member', 'email' => 'shared-member@example.com']);
    browserAttachMembership($target, $teamA->id, $alphaOne->id);
    browserAttachMembership($target, $teamB->id, $betaOne->id);

    $this->actingAs($admin);

    $page = visit('/admin/user/'.$target->id);

    $page->click('[role="tab"]:has-text("Teams")')->wait(1);

    // Own team: an editable role select is present and works.
    $page->assertPresent('[dusk="role-select-'.$teamA->id.'"]');

    // Foreign team: read-only text, no editable select.
    $page->assertPresent('[dusk="role-readonly-'.$teamB->id.'"]')
        ->assertNotPresent('[dusk="role-select-'.$teamB->id.'"]')
        ->assertNotPresent('[dusk="detach-'.$teamB->id.'"]');

    // The editable own-team select really mutates the pivot.
    $page->select('[dusk="role-select-'.$teamA->id.'"]', (string) $alphaTwo->id)->wait(2);

    expect(browserMembershipExists($target->id, $alphaTwo->id, $teamA->id))->toBeTrue();
});
