<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

require_once __DIR__.'/Support/helpers.php';

test('a visitor registers, gets their own team, and lands in the admin panel as super admin', function () {
    $teamName = 'Acme Inc';
    $email = 'founder@example.com';

    $page = visit('/register');

    $page->assertSee('Create your account');

    // Drive the real registration form end to end.
    $page->fill('#team', $teamName)
        ->fill('#name', 'Founder')
        ->fill('#email', $email)
        ->fill('#password', 'password')
        ->fill('#password_confirmation', 'password')
        ->press('Register')
        ->wait(2);

    // Lands in the admin panel (config aura.auth.redirect = /admin).
    $page->assertPathIs('/admin');

    $user = User::withoutGlobalScopes()->where('email', $email)->first();

    expect($user)->not->toBeNull();

    // Their own team exists and is the current team.
    $team = Team::withoutGlobalScopes()->where('name', $teamName)->first();

    expect($team)->not->toBeNull()
        ->and($user->current_team_id)->toBe($team->id);

    // Super Admin via the shared global `admin` role (attach-don't-mint): a
    // user_role pivot row carrying the team_id and the Global Role's id, whose
    // role row itself has a null team_id.
    $adminRole = globalAdminRole();

    expect($adminRole)->not->toBeNull()
        ->and($adminRole->team_id)->toBeNull()
        ->and(browserMembershipExists($user->id, $adminRole->id, $team->id))->toBeTrue();

    // A fresh instance resolves to Super Admin.
    expect(User::withoutGlobalScopes()->find($user->id)->isSuperAdmin())->toBeTrue();
});

test('registration rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $page = visit('/register');

    $page->fill('#team', 'Duplicate Team')
        ->fill('#name', 'Impostor')
        ->fill('#email', 'taken@example.com')
        ->fill('#password', 'password')
        ->fill('#password_confirmation', 'password')
        ->press('Register')
        ->wait(2);

    // Stays on the form with the validation error rendered inline; no new user.
    $page->assertPathIs('/register')
        ->assertSee('already been taken');

    expect(User::withoutGlobalScopes()->where('email', 'taken@example.com')->count())->toBe(1);
});
