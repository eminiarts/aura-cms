<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Tests\Resources\Post;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
});

describe('table rendering', function () {
    test('table can be rendered on index page', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

        $this->get(route('aura.post.index'))
            ->assertSeeLivewire(Index::class)
            ->assertSeeLivewire(Table::class);
    });

    test('table shows all configured input fields', function () {
        Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $response = $this->get(route('aura.post.index'));

        $response->assertOk()
            ->assertSee('ID')
            ->assertSee('Text')
            ->assertSee('Number')
            ->assertSee('Date')
            ->assertSee('Description')
            ->assertSee('Team')
            ->assertSee('User');
    });
});

describe('table default values', function () {
    test('table initializes with correct default settings', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());
    });

    test('table has empty initial state for filters and selection', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        expect($component->selected)->toBe([])
            ->and($component->sorts)->toBe([])
            ->and($component->search)->toBeNull();
    });
});

describe('custom user columns', function () {
    test('table respects user-defined columns', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        auth()->user()->updateOption('columns.'.$post->getType(), ['id', 'text', 'number']);

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', auth()->user()->getOptionColumns($post->getType()));

        expect($component->columns)
            ->toContain('id')
            ->toContain('text')
            ->toContain('number')
            ->not->toContain('date')
            ->not->toContain('description')
            ->not->toContain('tags')
            ->not->toContain('categories')
            ->not->toContain('team')
            ->not->toContain('user');

        $this->get(route('aura.post.index'))->assertOk();
    });

    test('table columns can be reordered by user', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        // Set initial columns with visibility
        auth()->user()->updateOption('columns.'.$post->getType(), [
            'id' => true,
            'text' => true,
            'number' => true,
            'description' => false,
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', auth()->user()->getOptionColumns($post->getType()));

        $response = $this->get(route('aura.post.index'))->assertOk();
        $response->assertSeeInOrder(['id', 'text', 'number']);

        // Reorder columns
        auth()->user()->updateOption('columns_sort.'.$post->getType(), ['number', 'id', 'text']);

        $response = $this->get(route('aura.post.index'))->assertOk();
        $response->assertSeeInOrder(['number', 'id', 'text']);
    });
});

describe('table rows display', function () {
    test('table displays posts ordered by id descending by default', function () {
        $post1 = Post::create([
            'title' => 'First Post',
            'content' => 'Content 1',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = Post::create([
            'title' => 'Second Post',
            'content' => 'Content 2',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post1])
            ->assertViewHas('rows', function ($rows) use ($post2, $post1) {
                $items = $rows->items();

                return count($items) === 2
                    && $items[0]->id === $post2->id
                    && $items[1]->id === $post1->id;
            });
    });

    test('table can fetch all row ids', function () {
        Post::create(['title' => 'Post 1', 'content' => 'Content', 'type' => 'Post', 'status' => 'publish']);
        Post::create(['title' => 'Post 2', 'content' => 'Content', 'type' => 'Post', 'status' => 'publish']);
        Post::create(['title' => 'Post 3', 'content' => 'Content', 'type' => 'Post', 'status' => 'publish']);

        $component = livewire(Table::class, ['query' => null, 'model' => new Post]);

        $allIds = $component->instance()->allTableRows();

        expect($allIds)->toHaveCount(3);
    });
});

describe('table component methods', function () {
    test('table dispatches tableMounted event on mount', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertDispatched('tableMounted');
    });

    test('table can refresh rows', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Call refreshRows should not throw
        $component->call('refreshRows');

        // After refresh, rows should still be available
        $component->assertViewHas('rows');
    });

    test('table responds to refreshTable event', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->dispatch('refreshTable')
            ->assertViewHas('rows');
    });

    test('table responds to refreshTableSelected event by clearing selection', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->set('selected', [$post->id])
            ->dispatch('refreshTableSelected');

        expect($component->selected)->toBe([]);
    });
});

describe('table create link', function () {
    test('table generates correct create link', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $createLink = $component->instance()->createLink;

        expect($createLink)->toBe(route('aura.'.$post->getSlug().'.create'));
    });
});
