<?php

use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

test('table can be paginated', function () {
    // Create 21 posts using the Post factory
    Post::factory()->count(21)->create();

    // Assert Posts are created
    expect(Post::count())->toBe(21);

    // Visit the Post Index Page
    $this->get(route('aura.post.index', 'Post'))
    ->assertSeeLivewire('aura::post-index')
    ->assertSeeLivewire('aura::table');

    $eleven = Post::skip(10)->first();

    // Test the Table Component
    $component = Livewire::test(Table::class, ['query' => null, 'model' => new Post()])
    // ->assertSee('Showing 1 to 10 of 21 results')
    ->assertSee('Showing')
    ->assertSee('1')
    ->assertSee('10')
    ->assertSee('21')
    ->assertSee('results')
    ->assertSet('perPage', 10)
    ->assertSet('page', 1)
    ->call('setPage', 2)
    ->assertSet('page', 2)
    ->assertSee($eleven->title)
    ;

});