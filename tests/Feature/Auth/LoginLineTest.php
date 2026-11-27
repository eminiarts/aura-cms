<?php

namespace Tests\Feature\Auth;

test('login panel shows the default line when login-line is null', function () {
    config(['aura.theme.login-line' => null]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('data-login-line', false)
        ->assertSee('Every record in one place.');
});

test('login panel shows a configured login-line instead of the default', function () {
    config(['aura.theme.login-line' => 'All your clients, one desk.']);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('All your clients, one desk.')
        ->assertDontSee('Every record in one place.');
});

test('login panel hides the line when login-line is false', function () {
    config(['aura.theme.login-line' => false]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('data-login-line', false)
        ->assertDontSee('Every record in one place.');
});
