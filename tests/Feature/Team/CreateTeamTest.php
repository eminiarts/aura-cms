<?php

use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
});

test('team can be changed', function () {
});

test('team can be deleted', function () {
});
