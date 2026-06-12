<?php

use Aura\Base\Livewire\Profile;
use Aura\Base\Livewire\TwoFactorAuthenticationForm;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Profile Component Rendering', function () {
    it('renders the profile component', function () {
        livewire(Profile::class)
            ->assertSee('Profile')
            ->assertSee('Personal Infos')
            ->assertSee('Password')
            ->assertSee('2FA')
            ->assertSee('Delete');
    });

    it('loads the profile page route', function () {
        $this->get(route('aura.profile'))
            ->assertOk()
            ->assertSeeLivewire(Profile::class);
    });
});

describe('Profile Updates', function () {
    it('updates the user profile name and email', function () {
        livewire(Profile::class)
            ->set('form.fields.name', 'Updated Name')
            ->set('form.fields.email', 'updated@example.com')
            ->set('form.fields.password', null)
            ->call('save')
            ->assertHasNoErrors();

        $user = auth()->user()->fresh();

        expect($user->name)->toBe('Updated Name');
        expect($user->email)->toBe('updated@example.com');
    });

    it('preserves password when not updating it', function () {
        $originalPassword = $this->user->password;

        livewire(Profile::class)
            ->set('form.fields.name', 'Name Only Update')
            ->call('save')
            ->assertHasNoErrors();

        $user = auth()->user()->fresh();

        expect($user->password)->toBe($originalPassword);
    });
});

describe('Password Updates', function () {
    it('updates the user password with valid current password', function () {
        livewire(Profile::class)
            ->set('form.fields.current_password', 'password')
            ->set('form.fields.password', 'new-Password123*&*&!!!')
            ->set('form.fields.password_confirmation', 'new-Password123*&*&!!!')
            ->call('save')
            ->assertHasNoErrors();

        $user = auth()->user()->fresh();

        expect(Hash::check('new-Password123*&*&!!!', $user->password))->toBeTrue();
    });

    it('validates password confirmation matches', function () {
        livewire(Profile::class)
            ->set('form.fields.current_password', 'password')
            ->set('form.fields.password', 'new-Password123!')
            ->set('form.fields.password_confirmation', 'different-password')
            ->call('save')
            ->assertHasErrors();
    });
});

describe('Account Deletion', function () {
    it('deletes the user account with correct password', function () {
        $user = $this->user;

        $this->get(route('aura.profile'))->assertSeeLivewire(Profile::class);

        Livewire::actingAs($this->user)->test(Profile::class)
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertRedirect('/');

        expect(auth()->user())->toBeNull();
        expect(User::find($user->id))->toBeNull();
    });

    it('fails to delete account with incorrect password', function () {
        livewire(Profile::class)
            ->set('password', 'incorrect-password')
            ->call('deleteUser', request())
            ->assertHasErrors(['password' => 'current_password']);
    });

    it('logs out user after account deletion', function () {
        Livewire::actingAs($this->user)->test(Profile::class)
            ->set('password', 'password')
            ->call('deleteUser');

        expect(auth()->check())->toBeFalse();
    });
});

describe('Two Factor Authentication', function () {
    it('can enable 2fa', function () {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication');

        $user = $this->user->fresh();

        expect($user->two_factor_secret)->not->toBeNull();
        expect($user->recoveryCodes())->toHaveCount(8);
    });

    it('generates 8 recovery codes when enabling 2fa', function () {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication');

        $user = $this->user->fresh();

        expect($user->recoveryCodes())->toHaveCount(8);
    });

    it('can regenerate recovery codes', function () {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        $component = Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication')
            ->call('regenerateRecoveryCodes');

        $user = $this->user->fresh();

        $component->call('regenerateRecoveryCodes');

        expect($user->recoveryCodes())->toHaveCount(8);
        expect(array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()))->toHaveCount(8);
    });

    it('can disable 2fa', function () {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        $component = Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication');

        $user = $this->user->fresh();

        $this->assertNotNull($user->fresh()->two_factor_secret);

        $component->call('disableTwoFactorAuthentication');

        expect($user->fresh()->two_factor_secret)->toBeNull();
    });

    it('clears recovery codes when disabling 2fa', function () {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication');

        $user = $this->user->fresh();
        expect($user->recoveryCodes())->toHaveCount(8);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('disableTwoFactorAuthentication');

        $user->refresh();
        expect($user->two_factor_recovery_codes)->toBeNull();
    });
});

describe('Profile Authorization', function () {
    it('requires authentication to access profile', function () {
        auth()->logout();

        $this->get(route('aura.profile'))
            ->assertRedirect();
    });
});
