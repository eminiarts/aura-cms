<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Tag;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $model = new TableFilterModel;

    Aura::fake();
    Aura::setModel($model);
});

// Create Resource for this test
class TableFilterModel extends Resource
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
                'slug' => 'meta',
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
            [
                'name' => 'Other Tags',
                'slug' => 'other_tags',
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
            [
                'name' => 'Number',
                'slug' => 'number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

describe('filter initialization', function () {
    test('table initializes with empty custom filter array', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        expect($post->isMetaField('meta'))->toBeTrue();

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        expect($component->filters)
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('custom');
    });
});

describe('contains operator', function () {
    beforeEach(function () {
        $this->post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $this->post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);
    });

    test('filter by meta field with contains operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta')
            ->and($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');

        // Filter for 'A' - should show post2
        $component->set('filters.custom.0.filters.0.value', 'A');
        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post2->id);

        // Filter for 'B' - should show post1
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post->id);

        // Filter for 'C' - should show no results
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter by tags with contains operator', function () {
        $post2OtherTags = TableFilterModel::create([
            'title' => 'Test Post 3',
            'content' => 'Test Content C',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'C',
            'other_tags' => [2],
        ]);

        $tags = Tag::get();

        $component = livewire(Table::class, ['query' => null, 'model' => $this->post]);

        $component->call('addFilterGroup');

        // Set filter for tags
        $component->set('filters.custom.0.filters.0.name', 'tags');
        $component->set('filters.custom.0.filters.0.value', $tags->first()->id);

        expect($component->filters['custom'][0]['filters'][0]['name'])->toBe('tags');
        expect($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');
        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe($tags->first()->id);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post->id);

        // Change to last tag
        $component->set('filters.custom.0.filters.0.value', $tags->last()->id);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post2->id);

        // Create and filter by non-existent tag
        $tag7 = Tag::create(['title' => 'Tag 7']);
        $component->set('filters.custom.0.filters.0.value', $tag7->id);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);

        // Clear filter - should show all posts
        $component->set('filters.custom.0.filters.0.value', null);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 3);

        // Test other_tags filter - lookup the second tag (ID=2) which is assigned to post2OtherTags
        $component->call('addFilterGroup');
        $component->set('filters.custom.1.filters.0.name', 'other_tags');
        $component->set('filters.custom.1.filters.0.value', 2);

        expect($component->filters['custom'][1]['filters'][0]['name'])->toBe('other_tags');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2OtherTags->id);
    });
});

describe('does_not_contain operator', function () {
    test('filter by meta field with does_not_contain operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta');

        $component->set('filters.custom.0.filters.0.operator', 'does_not_contain');
        expect($component->filters['custom'][0]['filters'][0]['operator'])->toBe('does_not_contain');

        // Exclude 'A' - should show post1
        $component->set('filters.custom.0.filters.0.value', 'A');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Exclude 'B' - should show post2
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Exclude 'C' - should show both
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

describe('starts_with operator', function () {
    test('filter by meta field with starts_with operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta')
            ->and($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');

        $component->set('filters.custom.0.filters.0.operator', 'starts_with');
        $component->set('filters.custom.0.filters.0.value', 'A');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A');

        // Should find post2 (starts with 'A')
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Filter for 'B' - should find post1
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Filter for 'C' - no matches
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('ends_with operator', function () {
    test('filter by meta field with ends_with operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'ends_with');
        $component->set('filters.custom.0.filters.0.value', 'meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('meta');

        // Should find post2 (ends with 'meta')
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Filter for 'amazing' - should find post1
        $component->set('filters.custom.0.filters.0.value', 'amazing');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Filter for 'C' - no matches
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('is operator', function () {
    test('filter by meta field with is (exact match) operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is');
        $component->set('filters.custom.0.filters.0.value', 'A custom meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A custom meta');

        // Exact match for post2
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Exact match for post1
        $component->set('filters.custom.0.filters.0.value', 'B amazing');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Partial match should not work
        $component->set('filters.custom.0.filters.0.value', 'A custom');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('comparison operators', function () {
    test('filter by meta field with greater_than operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '100',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'greater_than');
        $component->set('filters.custom.0.filters.0.value', '150');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('150');

        // Only post2 has value > 150
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // No results > 200
        $component->set('filters.custom.0.filters.0.value', '200');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter by meta field with less_than operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '100',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'less_than');
        $component->set('filters.custom.0.filters.0.value', '150');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('150');

        // Only post1 has value < 150
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // No results < 100
        $component->set('filters.custom.0.filters.0.value', '100');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('empty operators', function () {
    test('filter by number field with is_empty operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'number' => 100,
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is_empty');
        $component->set('filters.custom.0.filters.0.name', 'number');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBeNull();
    });

    test('filter by meta field with is_not_empty operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is_not_empty');
        $component->set('filters.custom.0.filters.0.name', 'meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBeNull();
        expect($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta');

        // Only post2 has non-empty meta
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Switch to is_empty - should find post1
        $component->set('filters.custom.0.filters.0.operator', 'is_empty');
        $component->set('filters.custom.0.filters.0.name', 'meta');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);
    });
});
