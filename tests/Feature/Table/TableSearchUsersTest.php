<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $role = Role::first();

    $this->user2 = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'roles' => [$role->id],
    ]);

    $this->user3 = User::factory()->create([
        'name' => 'Test User 2',
        'email' => 'user2@example.com',
        'roles' => [$role->id],
    ]);
});

describe('user table search', function () {
    test('search by email shows matching users', function () {
        $component = livewire(Table::class, ['model' => $this->user])
            ->assertSet('search', null);

        // Initially shows all 3 users
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 3);

        $component
            ->assertSeeHtml('Test User')
            ->assertSee('Test User 2')
            ->assertSee('test@example.com')
            ->assertSee('user2@example.com')
            ->set('search', 'user2')
            ->assertSee('user2@example.com')
            ->assertDontSee('test@example.com');

        expect($component->sorts)->toBe([]);
    });

    test('search by name shows matching users', function () {
        $component = livewire(Table::class, ['model' => $this->user]);

        $component->set('search', 'Test User');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) >= 1);
    });

    test('search with no matches returns empty results', function () {
        $component = livewire(Table::class, ['model' => $this->user]);

        $component->set('search', 'nonexistent@email.com');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});
