<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\Tag;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new TableSaveFilterModel);

    // Create Posts
    $this->resource = TableSaveFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'B',
        'tags' => [
            'Tag 1', 'Tag 2', 'Tag 3',
        ],
    ]);

    $this->resource2 = TableSaveFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'A',
        'tags' => [
            'Tag 3', 'Tag 4', 'Tag 5',
        ],
    ]);
});

// Create Resource for this test
class TableSaveFilterModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'resource';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
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

test('table filter - taxonomy filter2', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Get Tag 1 from DB
    $tag1 = Tag::where('title', 'Tag 1')->first();

    // Apply Tag 1 filter, set $filters['taxonomy']['tag'] to [$tag1->id]
    // $component->set('filters.taxonomy.tags', [$tag1->id]);

    $component->call('addFilterGroup');

    // Contains
    $component->set('filters.custom.0.filters.0.name', 'tags');
    $component->set('filters.custom.0.filters.0.operator', 'contains');
    $component->set('filters.custom.0.filters.0.value', [$tag1->id]);


    // $component->rows should have 1 item
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 1;
    });

    // Set custom filter 'meta' to 'B'
    $component->call('addFilterGroup');

    // $component->rows should have 1 item
    $component->set('filters.custom.1.filters.0.value', 'B');
    $component->set('filters.custom.1.filters.0.operator', 'is');

    // Expect filter.custom.0.name to be metafield
    expect($component->filters['custom'][1]['filters'][0]['name'])->toBe('metafield');


    // Save Filters to DB
    $component->call('saveFilter');

    // $filter.name should be required, so it should have errors
    $component->assertHasErrors('filter.name');

    // Set $filter.name to 'Test Filter'
    $component->set('filter.name', 'Test Filter');
    $component->set('filter.type', 'user');

    // Save Filters to DB
    $component->call('saveFilter');

    // Assert no errors
    $component->assertHasNoErrors();

    // Get DB options
    $db = DB::table('options')->where('name', 'like', 'user.'.$this->user->id.'.Post.filters.test-filter')->get();

    // $db should have 1 item
    expect($db)->toHaveCount(1);

    $filters = auth()->user()->getOption($post->getType().'.filters.*');

    // $filters should have 1 item
    expect($filters)->toHaveCount(1);

    // $filters should have a key 'Test Filter'
    expect($filters)->toHaveKey('test-filter');

    // $filters['Test Filter'][0] should have 2 items
    // expect($filters['Test Filter']['taxonomy'])->toHaveCount(1);
    expect($filters['test-filter']['custom'])->toHaveCount(2);

    // expect($filters)->toHaveKey('Test Filter.taxonomy.tags.0', '1');
    expect($filters)->toHaveKey('test-filter.custom.0.filters.0.name', 'tags');
    expect($filters)->toHaveKey('test-filter.custom.0.filters.0.operator', 'contains');
    expect($filters)->toHaveKey('test-filter.custom.0.filters.0.value', [$tag1->id]);

    // Filters and $component->userFilters should be the same
    expect($filters->toArray())->toBe($component->userFilters);

    // Assert $filter.name is empty
    expect($component->filter['name'])->toBe('');

    // Component rows should have 1 item
    $component->assertViewHas('rows', function ($rows) use ($post) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post->id;
    });

    // After a filter is saved, the current filter should be set to the saved filter
    expect($component->selectedFilter)->toBe('test-filter');
});

test('table filter - taxonomy filter can be deleted', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    DB::table('options')->insert([
        'name' => 'user.'.$this->user->id.'.Post.filters.test-filter',
        'value' => '{"custom":[{"filters":[{"name":"tags","operator":"contains","value":[303],"options":{"resource_type":"Aura\\\\Base\\\\Resources\\\\Tag"}}]}],"name":"Test Filter","public":false,"global":false,"slug":"test-filter"}',
        'team_id' => $this->user->currentTeam->id,
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Filter $component->userFilters should have 1 item
    expect($component->userFilters)->toHaveCount(1);

    // $component->userFilters should have a key 'test-filter'
    expect($component->userFilters)->toHaveKey('test-filter');

    // Set selected filter to 'test-filter'
    $component->set('selectedFilter', 'test-filter');

    // Should see "Delete Filter" button
    $component->assertSee('Delete Filter');

    // Click "Delete Filter" button
    $component->call('deleteFilter', 'test-filter');

    // $component->userFilters should have 0 items
    expect($component->userFilters)->toHaveCount(0);

    // $component->selectedFilter should be null
    expect($component->selectedFilter)->toBeNull();

    // $filters should be reset
    expect($component->filters)->toHaveKey('custom', []);
});

test('table filter - custom filter can be removed', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Set custom filter 'meta' to 'B'
    $component->call('addFilterGroup');

    // $component->rows should have 1 item
    $component->set('filters.custom.0.filters.0.value', 'B');
    $component->set('filters.custom.0.filters.0.operator', 'is');

    // $component->assertSeeHtml('wire:click="removeCustomFilter(\'0\')"');

    // Remove custom filter
    $component->call('removeCustomFilter', 0);

    // $component->rows should have 2 items
    $component->assertViewHas('rows', function ($rows) {
        return count($rows) == 2;
    });

    // $component->filters should have 0 items
    expect($component->filters['custom'])->toHaveCount(0);
});

test('table filter - filters can be reset', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Set custom filter 'metafield' to 'B'
    $component->call('addFilterGroup');

    // $component->rows should have 1 item
    $component->set('filters.custom.0.filters.0.value', 'B');
    $component->set('filters.custom.0.filters.0.operator', 'is');

    $component->assertSeeHtml('wire:click="resetFilter"');

    // Remove custom filter
    $component->call('resetFilter');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows) == 2;
    });

    // $component->filters should have 0 items
    expect($component->filters['custom'])->toHaveCount(0);

});
