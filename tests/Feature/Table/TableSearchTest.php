<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new SearchTableModel);
});

class SearchTableModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'resource';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'title',
                'searchable' => true,
            ],
            [
                'name' => 'Meta',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'metafield',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

describe('search functionality', function () {
    test('search by exact title matches single post', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post 1',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = SearchTableModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Initially both posts are visible
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);

        // Search for specific title
        $component->set('search', 'Test Post 1');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);
    });

    test('search by partial title matches multiple posts', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post 1',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = SearchTableModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->set('search', 'Test');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });

    test('search with no matches returns empty results', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post 1',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = SearchTableModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->set('search', 'Test1234432343');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('clearing search shows all posts again', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post 1',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = SearchTableModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter to one post
        $component->set('search', 'Test Post 1');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);

        // Clear search
        $component->set('search', '');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

describe('search with other filters', function () {
    test('search works in combination with column filters', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post 1',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'metafield' => 'B',
        ]);

        $post2 = SearchTableModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'metafield' => 'A',
        ]);

        $post3 = SearchTableModel::create([
            'title' => 'Different Title',
            'content' => 'Test Content C',
            'type' => 'Post',
            'status' => 'publish',
            'metafield' => 'B',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Search for "Test" (should match post1 and post2)
        $component->set('search', 'Test');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);

        // Add filter for metafield = 'B'
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'metafield');
        $component->set('filters.custom.0.filters.0.operator', 'is');
        $component->set('filters.custom.0.filters.0.value', 'B');

        // Should only return post1 (has "Test" in title AND metafield = 'B')
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);
    });
});

describe('search case sensitivity', function () {
    test('search is case insensitive', function () {
        $post = SearchTableModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Search with lowercase
        $component->set('search', 'test post');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);

        // Search with uppercase
        $component->set('search', 'TEST POST');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);
    });
});
