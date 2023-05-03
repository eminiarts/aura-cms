<?php

use Eminiarts\Aura\Http\Livewire\User\Profile;
use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;
use Eminiarts\Aura\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

it('renders the profile component', function () {
    livewire(Profile::class)
        ->assertSee('User details')
        ->assertSee('Personal Infos')
        ->assertSee('Password')
        ->assertSee('2FA')
        ->assertSee('Delete');
});

it('updates the user profile', function () {
    livewire(Profile::class)
        ->set('post.fields.name', 'Updated Name')
        ->set('post.fields.email', 'updated@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user = auth()->user()->fresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

it('updates the user password', function () {
    livewire(Profile::class)
        ->set('post.fields.current_password', 'password')
        ->set('post.fields.password', 'new-password')
        ->set('post.fields.password_confirmation', 'new-password')
        ->call('save')
        ->assertHasNoErrors();

    $user = auth()->user()->fresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('deletes the user account', function () {
    $user = $this->user;

    $request = $this->get(route('aura.profile'));

    $this->get(route('aura.profile'))->assertSeeLivewire('aura::profile');

    Livewire::actingAs($this->user)->test(Profile::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertRedirect('/');

    expect(auth()->user())->toBeNull();

    expect(User::find($user->id))->toBeNull();
});

it('fails to delete the user account with incorrect password', function () {
    livewire(Profile::class)
        ->set('password', 'incorrect-password')
        ->call('deleteUser', request())
        ->assertHasErrors(['password' => 'current_password']);
});

// Thanks to https://github.com/laravel/jetstream

test('profile - 2fa can be enabled', function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(TwoFactorAuthenticationForm::class)
        ->call('enableTwoFactorAuthentication');

    $user = $this->user->fresh();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->recoveryCodes())->toHaveCount(8);
});

test('recovery codes can be regenerated', function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(TwoFactorAuthenticationForm::class)
        ->call('enableTwoFactorAuthentication')
        ->call('regenerateRecoveryCodes');

    $user = $this->user->fresh();

    $component->call('regenerateRecoveryCodes');

    expect($user->recoveryCodes())->toHaveCount(8);
    expect(array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()))->toHaveCount(8);
});

test('two factor authentication can be disabled', function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(TwoFactorAuthenticationForm::class)
        ->call('enableTwoFactorAuthentication');

    $user = $this->user->fresh();

    $this->assertNotNull($user->fresh()->two_factor_secret);

    $component->call('disableTwoFactorAuthentication');

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
