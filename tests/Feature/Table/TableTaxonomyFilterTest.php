<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\User;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\Tag;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class TableTaxonomyFilterModel extends Resource
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
        ];
    }
}

test('table filter - taxonomy filter', function () {
    // Create a Posts
    $post = TableTaxonomyFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'tags' => [
            'Tag 1', 'Tag 2', 'Tag 3',
        ],
    ]);

    $post2 = TableTaxonomyFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'tags' => [
            'Tag 3', 'Tag 4', 'Tag 5',
        ],
    ]);

    // Mock the Builder instance
    $builderMock = $this->createMock(Builder::class);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // Get Tag 1 from DB
    $tag1 = Tag::where('title', 'Tag 1')->first();

    DB::listen(function ($query) {
        // Log the query to the console or store it for inspection
        Log::info($query->sql, $query->bindings, $query->time);
    });

    // Apply Tag 1 filter, set $filters['taxonomy']['tag'] to [$tag1->id]
    $component->set('filters.taxonomy.tags', [$tag1->id]);

    dump($tag1->id);

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // $post->id should be the same as $component->rows->items()[0]->id
    expect($post->id)->toBe($component->rows->items()[0]->id);

    // Get Tag 3 from DB
    $tag3 = Tag::where('title', 'Tag 3')->first();

    // Apply Tag 3 filter, set $filters['taxonomy']['tag'] to [$tag3->id]
    $component->set('filters.taxonomy.tag', [$tag3->id]);

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(2);

    // Get Tag 4 from DB
    $tag4 = Tag::where('title', 'Tag 4')->first();

    // Apply Tag 4 filter, set $filters['taxonomy']['tag'] to [$tag4->id]
    $component->set('filters.taxonomy.tag', [$tag4->id]);

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // $post2->id should be the same as $component->rows->items()[0]->id
    expect($post2->id)->toBe($component->rows->items()[0]->id);

    // Apply Tag1 and Tag4 filter, set $filters['taxonomy']['tag'] to [$tag1->id, $tag4->id]
    $component->set('filters.taxonomy.tag', [$tag1->id, $tag4->id]);

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(2);

    // Create a new Tag
    $tag6 = Tag::create([
        'title' => 'Tag 6',
        'slug' => 'tag-6',
    ]);

    // Apply Tag6 filter, set $filters['taxonomy']['tag'] to [$tag6->id]
    $component->set('filters.taxonomy.tags', [$tag6->id]);

    // $component->rows should have 0 items
    expect($component->rows->items())->toHaveCount(0);

    // Inspect SQL
    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "taxonomies" inner join "taxonomy_relations" on "taxonomies"."id" = "taxonomy_relations"."taxonomy_id" where "posts"."id" = "taxonomy_relations"."relatable_id" and "taxonomy_relations"."relatable_type" = ?');

    // First Binding should be TableTaxonomyFilterModel
    expect($component->rowsQuery->getBindings()[0])->toBe('TableTaxonomyFilterModel');

    // Second Binding should be $tag6->id
    expect($component->rowsQuery->getBindings()[1])->toBe($tag6->id);
})->skip('Skip for now until we decide on how to handle taxonomies');
