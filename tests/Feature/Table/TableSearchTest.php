<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Tests\Resources\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new SearchTableModel);

});

// Create Resource for this test
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

test('search table title', function () {

    $post = SearchTableModel::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'tags' => [
            'Tag 1', 'Tag 2', 'Tag 3',
        ],
    ]);

    $post2 = SearchTableModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'tags' => [
            'Tag 3', 'Tag 4', 'Tag 5',
        ],
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Apply Tag 1 filter, set $filters['taxonomy']['tag'] to [$tag1->id]
    // $component->set('filters.taxonomy.tags', [$tag1->id]);

    // $component->rows should have 1 item
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 2;
    });

    $component->set('search', 'Test Post 1');

    // $component->call('updatedSearch', 'Test Post 1');

    // dd($component->search);

    $component->assertViewHas('rows', function ($rows) {
        // dd(count($rows->items()));
        return count($rows->items()) === 1;
    });

    $component->set('search', 'Test');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 2;
    });

    $component->set('search', 'Test1234432343');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 0;
    });

});
