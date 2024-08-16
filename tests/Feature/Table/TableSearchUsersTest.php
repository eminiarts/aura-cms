<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $role = Role::first();

    $this->user2 = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'roles' => $role->id,
    ]);

    $this->user3 = User::factory()->create([
        'name' => 'Test User 2',
        'email' => 'user2@example.com',
        'roles' => $role->id,
    ]);

    // Maybe there is a better way to do this
    $role->users()->syncWithoutDetaching([$this->user2->id => ['resource_type' => Role::class]]);
    $role->users()->syncWithoutDetaching([$this->user3->id => ['resource_type' => Role::class]]);

});

test('search user by email', function () {
    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['model' => $this->user])
        ->assertSet('search', null);

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 3;
    });

    $component
        ->assertSeeHtml('Test User')
        ->assertSee('Test User 2')
        ->assertSee('test@example.com')
        ->assertSee('user2@example.com')
        ->set('search', 'user2')
        ->assertSee('user2@example.com')
        ->assertDontSee('test@example.com');

    // $component->sorts should be []
    $this->assertEmpty($component->sorts);
});
