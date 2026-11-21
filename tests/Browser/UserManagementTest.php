<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;

require_once __DIR__.'/Support/helpers.php';

/*
|--------------------------------------------------------------------------
| User management browser journeys (issue #56)
|--------------------------------------------------------------------------
|
| Real Chromium against the Testbench app: an admin creates a user with a role
| through the Roles field (AdvancedSelect) and that user logs in; a configured
| Global Admin sees users and teams across the whole instance in the global area.
|
*/

test('a team Super Admin creates a user with a role through the form, and that user logs in', function () {
    $admin = browserSuperAdmin('admin-password');
    $team = $admin->currentTeam;

    // A distinctively named Team Role so the picker search resolves to one row.
    $role = browserTeamRole($team->id, 'Content Team', 'content-team');

    $page = visit('/admin/user/create');

    $page->assertSee('Name');

    $page->fill('#resource-field-name', 'Created Member')
        ->fill('#aura_field_email', 'created-member@example.com')
        ->fill('#resource-field-password', 'Str0ng!Pass#2024');

    // Pick the role in the AdvancedSelect (open → search → click the option).
    browserPickSingleRole($page, 'Content Team');

    $page->press('Save')->wait(3);

    // The user row exists with a Membership carrying the picked role + team.
    $user = User::withoutGlobalScopes()->where('email', 'created-member@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(browserMembershipExists($user->id, $role->id, $team->id))->toBeTrue();

    // The created account really works: log out the admin and sign in as the new
    // user through the real login form → the admin panel.
    Auth::logout();

    $login = visit('/login');

    $login->fill('#email', 'created-member@example.com')
        ->fill('#password', 'Str0ng!Pass#2024')
        ->press('Log in')
        ->wait(2);

    $login->assertPathIs('/admin');
});

test('a Global Admin sees users across all teams where a plain Super Admin does not', function () {
    // A user whose ONLY Membership is in a foreign team the actor is not part of.
    $foreign = foreignTeam();
    $foreignMember = soleMemberOf($foreign);
    $foreignMember->forceFill(['name' => 'Foreign Only', 'email' => 'foreign-only@example.com'])->save();

    // A plain team Super Admin: their Users index is tenant-scoped.
    $superAdmin = browserSuperAdmin('sa-password');
    $this->actingAs($superAdmin);

    $saIndex = visit('/admin/user');

    $saIndex->assertSee($superAdmin->email)
        ->assertDontSee('foreign-only@example.com');

    // A Global Admin: the Users index is unscoped and lists the foreign-team user.
    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);

    $gaIndex = visit('/admin/global/user');

    $gaIndex->assertSee('foreign-only@example.com');
});

test('a Global Admin sees foreign teams in the global Teams index', function () {
    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);
    Team::factory()->createQuietly([
        'name' => 'Distant Tenant',
        'user_id' => User::factory()->create()->id,
    ]);

    expect($ga->getTeams()->pluck('name')->all())->not->toContain('Distant Tenant');
    visit('/admin/global/team')->assertSee('Distant Tenant');
});
