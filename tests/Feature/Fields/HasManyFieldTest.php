<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

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

// Create Resource for this test
class HasManyFieldModel extends Resource
{
    public static $singularName = 'HasMany Model';

    public static ?string $slug = 'hasmany-model';

    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Hasmany for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'validation' => 'numeric|nullable',
                'conditional_logic' => [],
                'suffix' => '%',
                'prefix' => 'CHF',
                'slug' => 'hasmany',
            ],
        ];
    }
}

test('HasMany Field not shown in Create', function () {
    $model = new HasManyFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertDontSee('Hasmany for Test');
});

test('HasMany Field shown on Edit', function () {
    $model = new HasManyFieldModel();

});

test('HasMany query Meta Fields with posts table', function () {
});

test('HasMany query with custom tables', function () {
});
