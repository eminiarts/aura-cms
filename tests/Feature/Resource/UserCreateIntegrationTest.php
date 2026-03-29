<?php

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('user create form renders with correct fields', function () {
    Livewire::test(Create::class, ['slug' => 'user'])
        ->assertSee('Create User')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertSee('Roles')
        ->assertDontSee('Current Team')
        ->assertDontSee('Avatar');
});

test('creating a user via the form stores the user in the database', function () {
    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'New Test User')
        ->set('form.fields.email', 'newuser@example.com')
        ->set('form.fields.password', 'Str0ng!Pass#2024')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'New Test User',
        'email' => 'newuser@example.com',
    ]);

    // Bypass TeamScope to find the newly created user
    $newUser = User::withoutGlobalScope(TeamScope::class)
        ->where('email', 'newuser@example.com')
        ->first();

    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('New Test User');
    expect(Hash::check('Str0ng!Pass#2024', $newUser->password))->toBeTrue();
});

test('created user can log in with the provided password', function () {
    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Login Test User')
        ->set('form.fields.email', 'logintest@example.com')
        ->set('form.fields.password', 'Str0ng!Pass#2024')
        ->call('save')
        ->assertHasNoErrors();

    // Log out the current admin
    Auth::logout();

    // Attempt to log in with the new user's credentials
    $loginSuccessful = Auth::attempt([
        'email' => 'logintest@example.com',
        'password' => 'Str0ng!Pass#2024',
    ]);

    expect($loginSuccessful)->toBeTrue();
    expect(Auth::user()->email)->toBe('logintest@example.com');
});

test('user created in a team context is assigned to the correct team', function () {
    $team = $this->user->currentTeam;
    $role = Role::where('team_id', $team->id)->where('slug', 'super_admin')->first();

    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Team User')
        ->set('form.fields.email', 'teamuser@example.com')
        ->set('form.fields.password', 'Str0ng!Pass#2024')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasNoErrors();

    $newUser = User::withoutGlobalScope(TeamScope::class)
        ->where('email', 'teamuser@example.com')
        ->first();

    expect($newUser)->not->toBeNull();

    // User should have the role with the correct team
    $userRoles = $newUser->roles;
    expect($userRoles)->not->toBeEmpty();
    expect($userRoles->first()->team_id)->toBe($team->id);
});

test('user created with a role can log in and access the correct team', function () {
    $team = $this->user->currentTeam;
    $role = Role::where('team_id', $team->id)->where('slug', 'super_admin')->first();

    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Full Flow User')
        ->set('form.fields.email', 'fullflow@example.com')
        ->set('form.fields.password', 'Str0ng!Pass#2024')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasNoErrors();

    // Log out admin
    Auth::logout();

    // Log in as new user
    $loginSuccess = Auth::attempt([
        'email' => 'fullflow@example.com',
        'password' => 'Str0ng!Pass#2024',
    ]);
    expect($loginSuccess)->toBeTrue();

    // Fetch the Aura User model (Auth::user() returns base Laravel User)
    $loggedInUser = User::withoutGlobalScope(TeamScope::class)
        ->where('email', 'fullflow@example.com')
        ->first();

    expect($loggedInUser)->not->toBeNull();

    // The user should belong to the team through their role
    $teamIds = $loggedInUser->teams()->pluck('teams.id');
    expect($teamIds)->toContain($team->id);

    // Switch to the team and verify
    $loggedInUser->switchTeam($team);
    $loggedInUser->refresh();
    expect($loggedInUser->current_team_id)->toBe($team->id);
    expect($loggedInUser->currentTeam->id)->toBe($team->id);
});

test('user creation requires name and email', function () {
    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', '')
        ->set('form.fields.email', '')
        ->call('save')
        ->assertHasErrors(['form.fields.name', 'form.fields.email']);
});

test('user creation requires a valid email', function () {
    Livewire::test(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Test User')
        ->set('form.fields.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['form.fields.email']);
});
