<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Hash;

describe('Password Update', function () {
    test('password can be updated with valid credentials', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put(route('aura.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'password-updated');

        expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    });

    test('current password must be correct', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put(route('aura.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');

        // Password should not have changed
        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    });

    test('password must be confirmed', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put(route('aura.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');

        // Password should not have changed
        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    });

    test('current password is required', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put(route('aura.password.update'), [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
    });

    test('new password is required', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put(route('aura.password.update'), [
                'current_password' => 'password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    });

    test('guest cannot update password', function () {
        $this->put(route('aura.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect();
    });
});
