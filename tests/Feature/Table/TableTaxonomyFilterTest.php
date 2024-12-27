<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Tests\Resources\Tag;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    Aura::fake();
    Aura::registerResources([TableTaxonomyFilterModel::class]);
    Aura::setModel(new TableTaxonomyFilterModel);

    // Create User
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
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

test('table filter - taxonomy filter', function () {
    // First create the tags
    $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
    $tag2 = Tag::create(['title' => 'Tag 2', 'slug' => 'tag-2']);
    $tag3 = Tag::create(['title' => 'Tag 3', 'slug' => 'tag-3']);
    $tag4 = Tag::create(['title' => 'Tag 4', 'slug' => 'tag-4']);
    $tag5 = Tag::create(['title' => 'Tag 5', 'slug' => 'tag-5']);

    // Create Posts with tag IDs instead of strings
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

    $posts = DB::table('posts')->get();
    ray($posts);

    // expect 2 posts to be created
    expect(TableTaxonomyFilterModel::count())->toBe(2);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    DB::listen(function ($query) {
        // Log the query to the console or store it for inspection
        // Log::info($query->sql, $query->bindings, $query->time);
    });

    $relations = DB::table('post_relations')->get();
    $posts = TableTaxonomyFilterModel::get();
    ray($relations, $posts);

    // Apply Tag 1 filter
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

    // Should have 1 item
    $component->assertViewHas('rows', function ($rows) use ($post) {
        ray($rows->items());

        return count($rows->items()) === 1 && $rows->items()[0]->id === $post->id;
    });

    // Apply Tag 3 filter (should show both posts)
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

    // Should have 2 items
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 2;
    });

    // Apply Tag 4 filter (should show only post2)
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

    // Should have 1 item and be post2
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id;
    });

    // Apply Tag1 and Tag4 filter (should show both posts)
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

    // Should have 2 items
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 2;
    });

    // Create a new Tag
    $tag6 = Tag::create([
        'title' => 'Tag 6',
        'slug' => 'tag-6',
    ]);

    // Apply Tag6 filter (should show no posts)
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

    // Should have 0 items
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 0;
    });

    // Should have 0 items
    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 0;
    });

    // Inspect the raw SQL query
    $rawSql = $component->instance()->rowsQuery()->toRawSql();
    expect($rawSql)->toContain('select * from "posts" where (("id" in (select "related_id" from "post_relations" where "post_relations"."related_type" = \'TableTaxonomyFilterModel\' and "post_relations"."resource_type" = \'Aura\Base\Tests\Resources\Tag\' and "post_relations"."slug" = \'tags\' and "post_relations"."resource_id" in (8)))) and "posts"."type" = \'TableTaxonomy\' order by "posts"."id" desc');

});
