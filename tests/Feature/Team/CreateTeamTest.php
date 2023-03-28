<?php

use Eminiarts\Aura\Models\User;

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

test('team can be created', function () {
})->todo();

test('team can be changed', function () {
})->todo();

test('team can be deleted', function () {
})->todo();
