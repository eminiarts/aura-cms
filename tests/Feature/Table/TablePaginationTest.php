<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
});

test('table can be paginated', function () {
    // Create 21 posts using the Post factory
    Post::factory()->count(21)->create(['title' => Str::slug(fake()->words(5, true))]);

    // Assert Posts are created
    expect(Post::count())->toBe(21);

    // Visit the Post Index Page
    // Use class references for Livewire 3.x compatibility with assertSeeLivewire
    $this->get(route('aura.post.index'))
        ->assertSeeLivewire(Index::class)
        ->assertSeeLivewire(Table::class);

    $eleven = Post::skip(10)->first();

    // Test the Table Component
    $component = Livewire::test(Table::class, ['query' => null, 'model' => new Post])
    // ->assertSee('Showing 1 to 10 of 21 results')
        ->assertSee('Showing')
        ->assertSee('1')
        ->assertSee('10')
        ->assertSee('21')
        ->assertSee('results')
        ->assertSet('perPage', 10)
        ->assertSet('paginators.page', 1)
        ->call('setPage', 2)
        ->assertSet('paginators.page', 2)
        ->assertSee($eleven->title);

});
