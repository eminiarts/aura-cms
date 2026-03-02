<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;

describe('Password Confirmation Screen', function () {
    test('confirmation screen renders for authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('aura.password.confirm'))
            ->assertSuccessful()
            ->assertSee('Password')
            ->assertSee('Confirm');
    });

    test('guest is redirected from confirmation screen', function () {
        $this->get(route('aura.password.confirm'))
            ->assertRedirect();
    });
});

describe('Password Confirmation', function () {
    test('password can be confirmed with valid credentials', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.password.confirm'), ['password' => 'password'])
            ->assertRedirect(config('aura.auth.redirect'))
            ->assertSessionHasNoErrors();
    });

    test('password confirmation sets session flag', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.password.confirm'), ['password' => 'password']);

        expect(session('auth.password_confirmed_at'))->not->toBeNull();
    });

    test('password confirmation fails with wrong password', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.password.confirm'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    });

    test('password field is required', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.password.confirm'), [])
            ->assertSessionHasErrors('password');
    });

    test('guest cannot confirm password', function () {
        $this->post(route('aura.password.confirm'), ['password' => 'password'])
            ->assertRedirect();
    });
});
