<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('user can be created', function () {
    // Get initial user count
    $initialCount = User::count();

    $this->withoutExceptionHandling();

    // Get first role for testing
    $role = Role::first();

    $component = livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Test User')
        ->set('form.fields.email', 'test@example.com')
        ->set('form.fields.current_team_id', $this->user->currentTeam->id)
        ->set('form.fields.password', 'password')
        ->set('form.fields.password_confirmation', 'password')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        // Minimum password length is 8, minimum 1 uppercase letter, minimum 1 number, minimum 1 special character
        ->assertHasErrors(['form.fields.password'])
        ->set('form.fields.password', 'Password123!XX')
        ->set('form.fields.password_confirmation', 'Password123!XX')

        ->call('save')
        ->assertHasNoErrors();

    // Assert user was created
    expect(User::count())->toBe($initialCount + 1);

    // Get the created user
    $user = User::where('email', 'test@example.com')->first();

    // Assert user properties
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect(Hash::check('Password123!XX', $user->password))->toBeTrue();
    expect($user->roles->pluck('id')->toArray())->toContain($role->id);
});

test('user cannot be created with invalid email', function () {
    $role = Role::first();

    livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Test User')
        ->set('form.fields.email', 'invalid-email')
        ->set('form.fields.password', 'password')
        ->set('form.fields.password_confirmation', 'password')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasErrors(['form.fields.email']);
});

test('user cannot be created with mismatched passwords', function () {
    $role = Role::first();

    livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Test User')
        ->set('form.fields.email', 'test@example.com')
        ->set('form.fields.password', 'password')
        ->set('form.fields.password_confirmation', 'different-password')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasErrors(['form.fields.password']);
});

test('user can be deleted', function () {
    // Create a user to be deleted
    $user = User::create([
        'name' => 'User to Delete',
        'email' => 'delete@example.com',
        'password' => Hash::make('password'),
    ]);

    $initialCount = User::count();

    // Delete the user
    $user->delete();

    // Assert user was deleted
    expect(User::count())->toBe($initialCount - 1);
    expect(User::where('email', 'delete@example.com')->first())->toBeNull();
});

test('user can be edited without changing password', function () {
    // Create a test user
    $user = User::create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'password' => 'OriginalPass123!',
    ]);

    $originalPassword = $user->password;

    Aura::fake();
    Aura::setModel($user);

    ray()->clearScreen();
    // Edit user without setting password
    livewire(Edit::class, ['id' => $user->id])
        ->set('form.fields.name', 'Updated Name')
        ->set('form.fields.email', 'updated@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    // Assert user details were updated
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    // Assert password remained unchanged
    expect($user->password)->toBe($originalPassword);
});

test('user password can be changed when explicitly set', function () {
    // Create a test user
    $user = User::create([
        'name' => 'Password Test',
        'email' => 'password@example.com',
        'password' => 'CurrentPass123!',
        'current_team_id' => Team::first()->id,
    ]);

    ray()->clearScreen();

    Aura::fake();
    Aura::setModel($user);

    // Change user password
    livewire(Edit::class, ['id' => $user->id])
        ->set('form.fields.password', 'NewPass123!qerqw')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    // Assert new password was saved and is correct
    expect(Hash::check('NewPass123!qerqw', $user->password))->toBeTrue();
    expect(Hash::check('CurrentPass123!', $user->password))->toBeFalse();
});
