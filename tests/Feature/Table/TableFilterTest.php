<?php

use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
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

// Create Resource for this test
class TableFilterModel extends Resource
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
                'slug' => 'meta',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Resources\\Tag',
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

test('table filter', function () {
    // Create a Posts
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    expect($post->isMetaField('meta'))->toBeTrue();
    expect($post->isTaxonomyField('meta'))->toBeFalse();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
        ->assertSet('tableView', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());

    // Filters should be "taxonomy" => array:2 [ "Tag" => [] ] "custom" => [] ]
    expect($component->filters)->toBeArray();
    expect($component->filters)->toHaveCount(2);
    expect($component->filters)->toHaveKey('taxonomy');
    expect($component->filters)->toHaveKey('custom');

    // Taxonomy Filters should be "Tag" => []
    expect($component->filters['taxonomy'])->toBeArray();
    expect($component->filters['taxonomy'])->toHaveCount(1);
    expect($component->filters['taxonomy'])->not()->toHaveKey('Category');
    expect($component->filters['taxonomy'])->toHaveKey('Tag');
});

test('table filter - custom filter - contains', function () {
    // Create a Posts
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // set custom filter to "A"
    $component->call('addFilter');

    // $component->filter['custom'] should have 1 item
    expect($component->filters['custom'])->toHaveCount(1);

    // expect the first item to have the keys: 'name', 'value', 'operator'
    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    // expect the first item name to be "meta"
    expect($component->filters['custom'][0]['name'])->toBe('meta');

    // expect the first item operator to be "contains"
    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    // On the component, set $filter['custom'][0]['value'] to "A"
    $component->set('filters.custom.0.value', 'A');

    // expect the first item value to be "A"
    expect($component->filters['custom'][0]['value'])->toBe('A');

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(1);

    // Id of the first item should be $post2->id
    expect($component->rows->items()[0]->id)->toBe($post2->id);

    // Change Filter to "B"
    $component->set('filters.custom.0.value', 'B');

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // Id of the first item should be $post->id
    expect($component->rows->items()[0]->id)->toBe($post->id);

    // Change Filter to "C"
    $component->set('filters.custom.0.value', 'C');

    // $component->rows should have 0 items
    expect($component->rows->items())->toHaveCount(0);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" like ?');

    // First Binding should be meta
    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    // Second Binding should be "C%"
    expect($component->rowsQuery->getBindings()[1])->toBe('%C%');
});

test('table filter - custom filter - does_not_contain', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    expect($component->filters['custom'][0]['name'])->toBe('meta');

    $component->set('filters.custom.0.operator', 'does_not_contain');

    expect($component->filters['custom'][0]['operator'])->toBe('does_not_contain');

    $component->set('filters.custom.0.value', 'A');

    expect($component->filters['custom'][0]['value'])->toBe('A');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'B');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'C');

    expect($component->rows->items())->toHaveCount(2);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" not like ?');

    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    expect($component->rowsQuery->getBindings()[1])->toBe('%C%');
});

test('table filter - custom filter - starts_with', function () {
    // Create a Posts
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B amazing',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A custom meta',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // set custom filter to "A"
    $component->call('addFilter');

    // $component->filter['custom'] should have 1 item
    expect($component->filters['custom'])->toHaveCount(1);

    // expect the first item to have the keys: 'name', 'value', 'operator'
    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    // expect the first item name to be "meta"
    expect($component->filters['custom'][0]['name'])->toBe('meta');

    // expect the first item operator to be "contains"
    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    // Set the operator to "starts_with"
    $component->set('filters.custom.0.operator', 'starts_with');

    // On the component, set $filter['custom'][0]['value'] to "A"
    $component->set('filters.custom.0.value', 'A');

    // expect the first item value to be "A"
    expect($component->filters['custom'][0]['value'])->toBe('A');

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(1);

    // Id of the first item should be $post2->id
    expect($component->rows->items()[0]->id)->toBe($post2->id);

    // Change Filter to "B"
    $component->set('filters.custom.0.value', 'B');

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // Id of the first item should be $post->id
    expect($component->rows->items()[0]->id)->toBe($post->id);

    // Change Filter to "C"
    $component->set('filters.custom.0.value', 'C');

    // $component->rows should have 0 items
    expect($component->rows->items())->toHaveCount(0);

    // Expect SQL to contain "select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" like ?"
    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" like ?');

    // First Binding should be meta
    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    // Second Binding should be "C%"
    expect($component->rowsQuery->getBindings()[1])->toBe('C%');

    // Inspect SQL inspect bindings
    // dd($component->rowsQuery->toSql(), $component->rowsQuery->getBindings());
    // dd($component->rowsQuery->toSql());

    // dd($component->filters);
});

test('table filter - custom filter - ends_with', function () {
    // Create a Posts
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B amazing',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A custom meta',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    expect($component->filters['custom'][0]['name'])->toBe('meta');

    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    $component->set('filters.custom.0.operator', 'ends_with');

    $component->set('filters.custom.0.value', 'meta');

    expect($component->filters['custom'][0]['value'])->toBe('meta');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'amazing');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'C');

    expect($component->rows->items())->toHaveCount(0);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" like ?');

    expect($component->rowsQuery->getBindings()[0])->toBe('meta');
    expect($component->rowsQuery->getBindings()[1])->toBe('%C');
});

test('table filter - custom filter - is', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B amazing',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A custom meta',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    expect($component->filters['custom'][0]['name'])->toBe('meta');

    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    $component->set('filters.custom.0.operator', 'is');

    $component->set('filters.custom.0.value', 'A custom meta');

    expect($component->filters['custom'][0]['value'])->toBe('A custom meta');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'B amazing');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'A custom');

    expect($component->rows->items())->toHaveCount(0);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" = ?');

    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    expect($component->rowsQuery->getBindings()[1])->toBe('A custom');
});

test('table filter - custom filter - greater_than', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '100',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '200',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    expect($component->filters['custom'][0]['name'])->toBe('meta');

    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    $component->set('filters.custom.0.operator', 'greater_than');

    $component->set('filters.custom.0.value', '150');

    expect($component->filters['custom'][0]['value'])->toBe('150');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', '200');

    expect($component->rows->items())->toHaveCount(0);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" > ?');

    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    expect($component->rowsQuery->getBindings()[1])->toBe('200');
});

test('table filter - custom filter - less_than', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '100',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '200',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    $component->set('filters.custom.0.operator', 'less_than');

    $component->set('filters.custom.0.value', '150');

    expect($component->filters['custom'][0]['value'])->toBe('150');

    expect($component->rows->items())->toHaveCount(1);

    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', '100');

    expect($component->rows->items())->toHaveCount(0);

    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ? and "value" < ?');

    expect($component->rowsQuery->getBindings()[0])->toBe('meta');

    expect($component->rowsQuery->getBindings()[1])->toBe('100');
});

test('table filter - custom filter - is_empty', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '200',
        'number' => 100,
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    $component->set('filters.custom.0.operator', 'is_empty');

    $component->set('filters.custom.0.name', 'number');

    expect($component->filters['custom'][0]['value'])->toBeNull();

    // expect($component->rows->items())->toHaveCount(1);

    // expect($component->rows->items()[0]->id)->toBe($post->id);

    // To do: Make it work with fields on the table
    // This does not work atm because we only filter meta fields (for now)
    // $component->set('filters.custom.0.name', 'title');
    // expect($component->rows->items())->toHaveCount(0);

    // Inspect sql when is_null is finished
    // expect($component->rowsQuery->toSql())->toContain('select * from "posts" where not exists (select * from "post_meta" where "posts"."id" = "post_meta"."post_id" and "key" = ?');

    // expect($component->rowsQuery->getBindings()[0])->toBe('meta');
});

test('table filter - custom filter - is_not_empty', function () {
    $post = TableFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '',
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => '200',
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    expect($component->filters['custom'])->toHaveCount(1);

    expect($component->filters['custom'][0])->toHaveKeys(['name', 'value', 'operator']);

    expect($component->filters['custom'][0]['name'])->toBe('meta');

    expect($component->filters['custom'][0]['operator'])->toBe('contains');

    $component->set('filters.custom.0.operator', 'is_not_empty');

    $component->set('filters.custom.0.value', '200');

    expect($component->filters['custom'][0]['value'])->toBe('200');

    expect($component->rows->items())->toHaveCount(1);

    //expect($component->rows->items()[0]->id)->toBe($post->id);

    // Check if the array has items.
    if (! empty($component->rows->items())) {
        expect($component->rows->items()[0]->id)->toBe($post2->id);
    } else {
        expect($component->rows->items())->toHaveCount(0);
    }

    $component->set('filters.custom.0.value', '');

    expect($component->rows->items())->toHaveCount(2);

    //expect($component->rows->items()[0]->id)->toBeLessThan($post2->id);

    //$component->set('filters.custom.0.value', '');

    //expect($component->rows->items())->toHaveCount(2);

    expect($component->rowsQuery->toSql())->toBe('select * from "posts" where "posts"."type" = ? and "posts"."team_id" = ? order by "posts"."id" desc limit 10 offset 0');

    expect($component->rowsQuery->getBindings()[0])->toBe('Post');
    //expect($component->rowsQuery->getBindings()[1])->toBe('');
});
