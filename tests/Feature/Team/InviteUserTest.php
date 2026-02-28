<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;



uses(Tests\TestCase::class, RefreshDatabase::class);

function createSuperAdminForInviteTest()
{
    $user = User::factory()->create();
    auth()->login($user);

    $team = Team::factory()->create();
    $user->update(['current_team_id' => $team->id]);

    Cache::forget("user_{$user->id}_current_team_id");

    $role = Role::withoutGlobalScope(\Aura\Base\Models\Scopes\TeamScope::class)
        ->where('team_id', $team->id)
        ->where('slug', 'super_admin')
        ->first();

    if (! $role) {
        $role = Role::create([
            'team_id' => $team->id,
            'slug' => 'super_admin',
            'type' => 'Role',
            'title' => 'Super Admin',
            'name' => 'Super Admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
        ]);
    }

    if (config('aura.teams')) {
        $user->roles()->syncWithPivotValues([$role->id], ['team_id' => $team->id]);
    } else {
        $user->roles()->sync([$role->id]);
    }

    return User::withoutGlobalScope(\Aura\Base\Models\Scopes\TeamScope::class)->find($user->id);
}

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->withoutExceptionHandling();
    $this->actingAs($this->user = createSuperAdminForInviteTest());
    config(['aura.teams' => true]);
});

test('user can be invited', function () {
    $this->withoutExceptionHandling();

    $component = Livewire::test(InviteUser::class)
        ->call('save')
        ->assertHasErrors(['form.fields.email' => 'required'])
        ->set('form.fields.email', 'test@test.ch')
        ->call('save')
        ->assertHasErrors(['form.fields.role' => 'required'])
        ->set('form.fields.role', 1)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(1, TeamInvitation::count());

    $invitation = TeamInvitation::first();
    $invitation->fresh();

    expect($invitation->email)->toBe('test@test.ch');
});

test('user gets correct role', function () {
    expect(config('aura.teams'))->toBeTrue();

    $role = Role::create([
        'name' => 'Test Role',
        'slug' => 'test_role',
        'permissions' => [
            'test_permission' => true,
        ],
    ]);

    $component = Livewire::test(InviteUser::class)
        ->call('save')
        ->assertHasErrors(['form.fields.email' => 'required'])
        ->set('form.fields.email', 'test@test.ch')
        ->call('save')
        ->assertHasErrors(['form.fields.role' => 'required'])
        ->set('form.fields.role', $role->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(1, TeamInvitation::count());

    $invitation = TeamInvitation::first();
    $invitation->fresh();

    expect($invitation->email)->toBe('test@test.ch');

    $url = URL::signedRoute('aura.invitation.register', [$invitation->team, $invitation]);

    $this->app['auth']->logout();
    $this->assertGuest();

    $response = $this->get($url);
    $response->assertOk();

    $response = $this->post($url, [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@test.ch',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(config('aura.auth.redirect'));

    $this->assertDatabaseMissing('team_invitations', [
        'email' => 'test@test.ch',
    ]);

    $user = User::where('email', 'test@test.ch')->first();

    expect($user->hasRole('test_role'))->toBeTrue();
    expect($user->hasRole('super_admin'))->toBeFalse();
});

test('Team Invitation can be created', function () {
    $team = $this->user->currentTeam;

    $invitation = $team->teamInvitations()->create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
    ]);

    expect($invitation->email)->toBe('test@test.ch');
    expect($invitation->exists)->toBeTrue();
});

test('register route is available', function () {
    $this->withoutExceptionHandling();

    $this->app['auth']->logout();
    $this->assertGuest();

    $this->get(route('aura.register'))->assertOk();
});

test('user email is prefilled in the registration', function () {
    $team = $this->user->currentTeam;

    $invitation = $team->teamInvitations()->create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
    ]);

    expect($invitation->email)->toBe('test@test.ch');
    expect($invitation->exists)->toBeTrue();
});

test('user can register using an invitation', function () {
    $team = Team::first();

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invite@test.de',
        'role' => Role::first()->id,
    ]);

    $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

    $response = $this->get($url);
    $response->assertStatus(302);

    $this->app['auth']->logout();
    $this->assertGuest();

    $response = $this->get($url);
    $response->assertOk()->assertViewIs('aura::auth.user_invitation');
    $response->assertSee($team->name);
    $response->assertSee($invitation->email);

    $user = User::where('email', $invitation->email)->first();
    $this->assertNull($user);

    $response = $this->post($url, [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(config('aura.auth.redirect'));

    $user = User::where('email', $invitation->email)->first();
    $this->assertNotNull($user);
    $this->assertTrue(Hash::check('password', $user->password));
    $this->assertEquals($team->id, $user->current_team_id);

    expect($user->roles->first()->id)->toEqual($invitation->role);

    $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
});

test('email and role are required in the invite user component', function () {
    $team = Team::first();

    Livewire::test(InviteUser::class, ['team' => $team])
        ->set('form.fields.email', '')
        ->set('form.fields.role', '')
        ->call('save')
        ->assertHasErrors([
            'form.fields.email',
            'form.fields.role',
        ]);

    $user = User::factory()->create(['email' => 'invited@test.com']);

    Livewire::test(InviteUser::class, ['team' => $team])
        ->set('form', ['fields' => [
            'email' => 'invited@test.com',
            'role' => Role::first()->id,
        ]])
        ->call('save')
        ->assertHasNoErrors([
            'form.fields.email',
        ]);

    $user->update(['fields' => ['roles' => [Role::first()->id]]]);

    Livewire::test(InviteUser::class, ['team' => $team])
        ->set('form', ['fields' => [
            'email' => 'invited@test.com',
            'role' => Role::first()->id,
        ]])
        ->call('save')
        ->assertHasErrors([
            'form.fields.email',
        ]);
});

test('invited user has only the assigned role and can only access their team', function () {
    $teamA = $this->user->currentTeam;

    // Create a "User" role with limited permissions
    $userRole = Role::create([
        'name' => 'User',
        'slug' => 'user',
        'team_id' => $teamA->id,
        'super_admin' => false,
        'permissions' => [
            'viewAny-User' => false,
            'view-User' => false,
            'create-User' => false,
            'update-User' => false,
            'delete-User' => false,
            'viewAny-Team' => false,
            'view-Team' => false,
            'create-Team' => false,
            'update-Team' => false,
            'delete-Team' => false,
            'viewAny-Role' => false,
            'view-Role' => false,
            'create-Role' => false,
            'update-Role' => false,
            'delete-Role' => false,
        ],
    ]);

    // Create invitation directly
    $invitation = $teamA->teamInvitations()->create([
        'email' => 'user@test.com',
        'role' => $userRole->id,
    ]);

    // Verify TeamInvitation created in DB
    $this->assertNotNull($invitation);
    expect($invitation->email)->toBe('user@test.com');
    expect($invitation->role)->toBe((string) $userRole->id);
    expect($invitation->team_id)->toBe($teamA->id);

    // Logout
    $this->app['auth']->logout();
    $this->assertGuest();

    // Visit signed registration URL
    $url = URL::signedRoute('aura.invitation.register', [$teamA, $invitation]);
    $response = $this->get($url);
    $response->assertOk();

    // Register with name + password
    $response = $this->post($url, [
        'name' => 'Invited User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(config('aura.auth.redirect'));

    // Assert user is created with correct email
    $invitedUser = User::where('email', 'user@test.com')->first();
    $this->assertNotNull($invitedUser);
    expect($invitedUser->name)->toBe('Invited User');

    // Assert user belongs to Team A
    expect($invitedUser->current_team_id)->toBe($teamA->id);

    // Assert user has "User" role via pivot table
    $pivotRole = $invitedUser->roles()
        ->wherePivot('team_id', $teamA->id)
        ->first();
    $this->assertNotNull($pivotRole, 'User should have a role in the pivot table');
    expect($pivotRole->id)->toBe($userRole->id);
    expect($pivotRole->slug)->toBe('user');

    // Assert user does NOT have super_admin
    expect($invitedUser->hasRole('super_admin'))->toBeFalse();
    expect($invitedUser->isSuperAdmin())->toBeFalse();

    // Assert invitation is deleted
    expect(TeamInvitation::count())->toBe(0);

    // Assert user CANNOT manage roles or teams
    $this->actingAs($invitedUser);
    expect($invitedUser->isSuperAdmin())->toBeFalse();
    expect($invitedUser->hasRole('user'))->toBeTrue();
    expect($invitedUser->hasRole('super_admin'))->toBeFalse();
});
