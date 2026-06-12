<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
});

describe('table pagination', function () {
    test('table displays correct pagination info', function () {
        Post::factory()->count(21)->create(['title' => Str::slug(fake()->words(5, true))]);

        expect(Post::count())->toBe(21);

        $this->get(route('aura.post.index'))
            ->assertSeeLivewire(Index::class)
            ->assertSeeLivewire(Table::class);

        $eleven = Post::skip(10)->first();

        livewire(Table::class, ['query' => null, 'model' => new Post])
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

    test('pagination shows correct range on each page', function () {
        Post::factory()->count(25)->create();

        expect(Post::count())->toBe(25);

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertSet('paginators.page', 1);

        // Page 1: shows items 1-10
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 10);

        // Page 2: shows items 11-20
        $component->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 10);

        // Page 3: shows items 21-25
        $component->call('setPage', 3)
            ->assertSet('paginators.page', 3)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 5);
    });

    test('can navigate between pages', function () {
        Post::factory()->count(30)->create();

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertSet('paginators.page', 1);

        // Go to page 2
        $component->call('setPage', 2)
            ->assertSet('paginators.page', 2);

        // Go to page 3
        $component->call('setPage', 3)
            ->assertSet('paginators.page', 3);

        // Go back to page 1
        $component->call('setPage', 1)
            ->assertSet('paginators.page', 1);
    });

    test('per page setting controls items per page', function () {
        Post::factory()->count(30)->create();

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertSet('perPage', 10)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 10);

        // Change to 25 per page
        $component->set('perPage', 25)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 25);
    });
});

describe('pagination edge cases', function () {
    test('single page of results shows all items', function () {
        Post::factory()->count(5)->create();

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 5);
    });

    test('empty results show no pagination', function () {
        // No posts created

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('exact page boundary works correctly', function () {
        Post::factory()->count(20)->create();

        $component = livewire(Table::class, ['query' => null, 'model' => new Post])
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 10);

        // Page 2 should have exactly 10 items
        $component->call('setPage', 2)
            ->assertViewHas('rows', fn ($rows) => count($rows->items()) === 10);
    });
});
