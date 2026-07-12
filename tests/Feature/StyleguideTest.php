<?php

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('styleguide page renders', function () {
    $this->get(route('aura.styleguide'))
        ->assertOk()
        ->assertSee('Style Guide')
        ->assertSee('Typography')
        ->assertSee('Buttons')
        ->assertSee('Notifications');
});

test('styleguide requires authentication', function () {
    auth()->logout();

    $this->get(route('aura.styleguide'))
        ->assertRedirect();
});
