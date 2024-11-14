<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    Aura::fake();
    Aura::registerResources([
        GlobalSearchModel::class,
    ]);
    Aura::setModel(new GlobalSearchModel);

    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class GlobalSearchModel extends Resource
{
    public static $singularName = 'SearchPost';

    public static ?string $slug = 'searchpost';

    public static string $type = 'SearchPost';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'title',
            ],
            [
                'name' => 'Content2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'content2',
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => false,
                'slug' => 'description',
            ],
        ];
    }

    public function title()
    {
        return $this->title;
    }
}

test('global search can find models by title', function () {

    // Create test models
    $posts = collect([
        GlobalSearchModel::create([
            'title' => 'First Test Post',
            'content2' => 'Some content here',
            'description' => 'Not searchable field',
        ]),
        GlobalSearchModel::create([
            'title' => 'Second Test Post',
            'content2' => 'Different content',
            'description' => 'Also not searchable',
        ]),
        GlobalSearchModel::create([
            'title' => 'Third Special Post',
            'content2' => 'More test content',
            'description' => 'Hidden description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Fourth Regular Post',
            'content2' => 'Regular content',
            'description' => 'Regular description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Fifth Unique Post',
            'content2' => 'Unique content',
            'description' => 'Unique description',
        ]),
    ]);

    // Test global search component
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Special')
        ->assertSee('Third Special Post')
        ->assertDontSee('First Test Post')
        ->assertDontSee('Second Test Post');

    // Test searching by content
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unique content')
        ->assertSee('Fifth Unique Post');

    // Test partial matches
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Test')
        ->assertSee('First Test Post')
        ->assertSee('Second Test Post');
});

test('global search respects searchable field configuration', function () {

    $post = GlobalSearchModel::create([
        'title' => 'Searchable Title',
        'content2' => 'Searchable Content',
        'description' => 'Unsearchable Description',
    ]);

    // Should find by title
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Title')
        ->assertSee('Searchable Title');

    // Should find by content
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Content')
        ->assertSee('Searchable Title');

    // Should not find by description
    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unsearchable Description')
        ->assertDontSee('Searchable Title');
});

test('global search can be disabled via config', function () {

    // Create a test model
    $post = GlobalSearchModel::create([
        'title' => 'Test Post',
        'content2' => 'Test Content',
        'description' => 'Test Description',
    ]);

    // First verify search works with feature enabled
    config(['aura.features.global_search' => true]);

    $this->get(route('aura.index'))
        ->assertOk()
        ->assertSee('global-search');

    // Now disable global search
    config(['aura.features.global_search' => false]);

    $this->get(route('aura.index'))
        ->assertOk()
        ->assertDontSee('global-search');

    // Verify the component doesn't work when disabled
    Livewire::test(GlobalSearch::class)
        ->assertStatus(403);
})->skip();

test('global search returns empty when no matches found', function () {

    // Create some test models
    $posts = collect([
        GlobalSearchModel::create([
            'title' => 'First Post',
            'content2' => 'Some content',
            'description' => 'Description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Second Post',
            'content2' => 'Other content',
            'description' => 'Other description',
        ]),
    ]);

    // Search for non-existent term
    Livewire::test(GlobalSearch::class)
        ->set('search', 'NonExistentTerm')
        ->assertSee('No results')
        ->assertDontSee('First Post')
        ->assertDontSee('Second Post');
});
