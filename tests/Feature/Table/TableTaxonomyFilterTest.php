<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Tag;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([TableTaxonomyFilterModel::class]);
    Aura::setModel(new TableTaxonomyFilterModel);

    $this->actingAs($this->user = createSuperAdmin());
});

class TableTaxonomyFilterModel extends Resource
{
    public static $singularName = 'TableTaxonomy';

    public static ?string $slug = 'tabletaxonomy';

    public static string $type = 'TableTaxonomy';

    protected $table = 'posts';

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
        ];
    }
}

describe('taxonomy filter with Tags field', function () {
    test('filter by single tag returns matching posts', function () {
        // Create tags first
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag2 = Tag::create(['title' => 'Tag 2', 'slug' => 'tag-2']);
        $tag3 = Tag::create(['title' => 'Tag 3', 'slug' => 'tag-3']);
        $tag4 = Tag::create(['title' => 'Tag 4', 'slug' => 'tag-4']);
        $tag5 = Tag::create(['title' => 'Tag 5', 'slug' => 'tag-5']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id, $tag2->id, $tag3->id],
        ]);

        $post2 = TableTaxonomyFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [$tag3->id, $tag4->id, $tag5->id],
        ]);

        expect(TableTaxonomyFilterModel::count())->toBe(2);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter by Tag 1 - should only return post1
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag1->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);
    });

    test('filter by shared tag returns all matching posts', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag3 = Tag::create(['title' => 'Tag 3', 'slug' => 'tag-3']);
        $tag5 = Tag::create(['title' => 'Tag 5', 'slug' => 'tag-5']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id, $tag3->id],
        ]);

        $post2 = TableTaxonomyFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [$tag3->id, $tag5->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter by Tag 3 (shared tag) - should return both posts
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag3->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });

    test('filter by exclusive tag returns only matching post', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag4 = Tag::create(['title' => 'Tag 4', 'slug' => 'tag-4']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id],
        ]);

        $post2 = TableTaxonomyFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [$tag4->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter by Tag 4 - should only return post2
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag4->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);
    });

    test('filter by multiple tags returns posts with any matching tag', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag4 = Tag::create(['title' => 'Tag 4', 'slug' => 'tag-4']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id],
        ]);

        $post2 = TableTaxonomyFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [$tag4->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter by Tag1 OR Tag4 - should return both posts
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag1->id, $tag4->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });

    test('filter by unused tag returns no posts', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag6 = Tag::create(['title' => 'Tag 6', 'slug' => 'tag-6']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter by unused Tag 6 - should return no posts
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag6->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter generates correct SQL query', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'contains',
                'value' => [$tag1->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        $rawSql = $component->instance()->rowsQuery()->toRawSql();

        // Verify the query contains expected structure
        expect($rawSql)
            ->toContain('select * from "posts"')
            ->toContain('post_relations')
            ->toContain('resource_type')
            ->toContain('tags');
    });
});

describe('taxonomy filter with does_not_contain operator', function () {
    test('filter by tag with does_not_contain excludes matching posts', function () {
        $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
        $tag2 = Tag::create(['title' => 'Tag 2', 'slug' => 'tag-2']);

        $post = TableTaxonomyFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [$tag1->id],
        ]);

        $post2 = TableTaxonomyFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [$tag2->id],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Filter posts that DO NOT contain Tag 1
        $component->set('filters.custom', [[
            'filters' => [[
                'name' => 'tags',
                'operator' => 'does_not_contain',
                'value' => [$tag1->id],
                'options' => [
                    'resource_type' => 'Aura\\Base\\Resources\\Tag',
                ],
            ]],
        ]]);

        // Should only return post2 (which doesn't have tag1)
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);
    });
});
