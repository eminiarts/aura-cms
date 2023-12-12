<?php

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Taxonomies\Tag;
use Illuminate\Support\Facades\DB;
use Eminiarts\Aura\Livewire\Table\Table;
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

    // Create Posts
    $this->post = TableSaveFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'B',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $this->post2 = TableSaveFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'A',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);
});

// Create Resource for this test
class TableSaveFilterModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'post';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Meta',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'metafield',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Tag',
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

test('table filter - taxonomy filter', function () {
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Get Tag 1 from DB
    $tag1 = Tag::where('name', 'Tag 1')->first();

    // Apply Tag 1 filter, set $filters['taxonomy']['tag'] to [$tag1->id]
    $component->set('filters.taxonomy.Tag', [$tag1->id]);

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // Set custom filter 'meta' to 'B'
    $component->call('addFilter');

    // $component->rows should have 1 item
    $component->set('filters.custom.0.value', 'B');
    $component->set('filters.custom.0.operator', 'is');

    // Expect filter.custom.0.name to be metafield
    expect($component->filters['custom'][0]['name'])->toBe('metafield');

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
    $db = DB::table('options')->where('name', 'like', 'user.'.$this->user->id.'.Post.filters.Test Filter')->get();

    // $db should have 1 item
    expect($db)->toHaveCount(1);

    $filters = auth()->user()->getOption($post->getType().'.filters.*');

    // $filters should have 1 item
    expect($filters)->toHaveCount(1);

    // $filters should have a key 'Test Filter'
    expect($filters)->toHaveKey('Test Filter');

    // $filters['Test Filter'][0] should have 2 items
    expect($filters['Test Filter']['taxonomy'])->toHaveCount(1);
    expect($filters['Test Filter']['custom'])->toHaveCount(1);

    // $filters['Test Filter'][0]['taxonomy'] should have 1 item
    expect($filters['Test Filter']['taxonomy']['Tag'])->toHaveCount(1);

    expect($filters)->toHaveKey('Test Filter.taxonomy.Tag.0', '1');
    expect($filters)->toHaveKey('Test Filter.custom.0.name', 'metafield');
    expect($filters)->toHaveKey('Test Filter.custom.0.operator', 'is');
    expect($filters)->toHaveKey('Test Filter.custom.0.value', 'B');

    // Filters and $component->userFilters should be the same

    expect($filters->toArray())->toBe($component->userFilters);

    // Assert $filter.name is empty
    expect($component->filter['name'])->toBe('');

    // Component rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // $post->id should be the same as $component->rows->items()[0]->id
    expect($post->id)->toBe($component->rows->items()[0]->id);

    // After a filter is saved, the current filter should be set to the saved filter
    expect($component->selectedFilter)->toBe('Test Filter');
});

test('table filter - taxonomy filter can be deleted', function () {
    $post = $this->post;
    $post2 = $this->post2;

    DB::table('options')->insert([
        'name' => 'user.'.$this->user->id.'.Post.filters.Test Filter',
        'value' => '{"taxonomy":{"Tag":[1]},"custom":[{"name":"metafield","operator":"is","value":"B"}]}',
        'team_id' => $this->user->currentTeam->id,
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Filter $component->userFilters should have 1 item
    expect($component->userFilters)->toHaveCount(1);

    // $component->userFilters should have a key 'Test Filter'
    expect($component->userFilters)->toHaveKey('Test Filter');

    // Set selected filter to 'Test Filter'
    $component->set('selectedFilter', 'Test Filter');

    // Should see "Delete Filter" button
    $component->assertSee('Delete Filter');

    // Click "Delete Filter" button
    $component->call('deleteFilter', 'Test Filter');

    // $component->userFilters should have 0 items
    expect($component->userFilters)->toHaveCount(0);

    // $component->selectedFilter should be null
    expect($component->selectedFilter)->toBeNull();

    // $filters should be reset
    expect($component->filters)->toHaveKey('custom', []);
    expect($component->filters)->toHaveKey('taxonomy.Tag', []);
});

test('table filter - custom filter can be removed', function () {
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Set custom filter 'meta' to 'B'
    $component->call('addFilter');

    // $component->rows should have 1 item
    $component->set('filters.custom.0.value', 'B');
    $component->set('filters.custom.0.operator', 'is');

    $component->assertSeeHtml('wire:click="removeCustomFilter(\'0\')"');

    // Remove custom filter
    $component->call('removeCustomFilter', 0);

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(2);

    // $component->filters should have 0 items
    expect($component->filters['custom'])->toHaveCount(0);
});

test('table filter - filters can be reset', function () {
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Set custom filter 'metafield' to 'B'
    $component->call('addFilter');

    // $component->rows should have 1 item
    $component->set('filters.custom.0.value', 'B');
    $component->set('filters.custom.0.operator', 'is');

    $component->assertSeeHtml('wire:click="resetFilter"');

    // Remove custom filter
    $component->call('resetFilter');

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(2);

    // $component->filters should have 0 items
    expect($component->filters['custom'])->toHaveCount(0);

    // taxonomy filter should be reset
    expect($component->filters['taxonomy'])->toHaveCount(1);
});
