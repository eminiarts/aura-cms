<?php

use Livewire\Livewire;

use Illuminate\Http\Request;
use Eminiarts\Aura\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\Store as SessionStore;

use Eminiarts\Aura\Http\Livewire\User\Profile;

use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;

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

    // Visit route aura.profile
    $this->get(route('aura.profile'))->assertSeeLivewire('aura::profile');

    // dd(request()->session());

    Livewire::actingAs($this->user)->test(Profile::class) // Pass the request to the Livewire component
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


test('profile - 2fa can be enabled', function () {
    $user = $this->user;

    $this->withSession(['auth.password_confirmed_at' => time()]);


    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication');

    $user = $user->fresh();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->recoveryCodes())->toHaveCount(8);
});
