<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $role = Role::first();

    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->user2 = User::factory()->create([
        'name' => 'Test User 2',
        'email' => 'user2@example.com',
    ]);

    $team = Team::first();

    $team->users()->attach($this->user->id, [
        'key' => 'roles',
        'value' => $role->id,
    ]);
    $team->users()->attach($this->user2->id, [
        'key' => 'roles',
        'value' => $role->id,
    ]);

});

test('search user by email', function () {

    $this->withoutExceptionHandling();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['model' => $this->user])
        ->assertSet('search', null);

    // $html = $component->html();
    // ray($html);
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
