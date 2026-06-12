<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Livewire\Livewire;

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([
        GlobalSearchModel::class,
    ]);
    Aura::setModel(new GlobalSearchModel);

    $this->actingAs($this->user = createSuperAdmin());
});

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

test('can find models by title', function () {
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

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Special')
        ->assertSee('Third Special Post')
        ->assertDontSee('First Test Post')
        ->assertDontSee('Second Test Post');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unique content')
        ->assertSee('Fifth Unique Post');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Test')
        ->assertSee('First Test Post')
        ->assertSee('Second Test Post');
});

test('respects searchable field configuration', function () {
    $post = GlobalSearchModel::create([
        'title' => 'Searchable Title',
        'content2' => 'Searchable Content',
        'description' => 'Unsearchable Description',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Title')
        ->assertSee('Searchable Title');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Content')
        ->assertSee('Searchable Title');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unsearchable Description')
        ->assertDontSee('Searchable Title');
});

test('can be disabled via config', function () {
    $post = GlobalSearchModel::create([
        'title' => 'Test Post',
        'content2' => 'Test Content',
        'description' => 'Test Description',
    ]);

    config(['aura.features.global_search' => true]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('global-search');

    config(['aura.features.global_search' => false]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('global-search');

    Livewire::test(GlobalSearch::class)
        ->assertStatus(403);
});

test('returns empty when no matches found', function () {
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

    Livewire::test(GlobalSearch::class)
        ->set('search', 'NonExistentTerm')
        ->assertSee('No results')
        ->assertDontSee('First Post')
        ->assertDontSee('Second Post');
});
