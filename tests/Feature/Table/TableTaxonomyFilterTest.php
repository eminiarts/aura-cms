<?php

use App\Aura\Resources\Post;
use App\Aura\Taxonomies\Tag;
use App\Http\Livewire\Table\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
class TableTaxonomyFilterModel extends Post
{
    public static $singularName = 'Post';

    public static ?string $slug = 'post';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Meta',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'meta',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'App\\Aura\\Fields\\Tags',
                'model' => 'App\\Aura\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
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
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $post2 = TableTaxonomyFilterModel::create([
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

    // Get Tag 1 from DB
    $tag1 = Tag::where('name', 'Tag 1')->first();

    // Apply Tag 1 filter, set $filters['taxonomy']['tag'] to [$tag1->id]
    $component->set('filters.taxonomy.tag', [$tag1->id]);

    // $component->rows should have 1 item
    expect($component->rows->items())->toHaveCount(1);

    // $post->id should be the same as $component->rows->items()[0]->id
    expect($post->id)->toBe($component->rows->items()[0]->id);

    // Get Tag 3 from DB
    $tag3 = Tag::where('name', 'Tag 3')->first();

    // Apply Tag 3 filter, set $filters['taxonomy']['tag'] to [$tag3->id]
    $component->set('filters.taxonomy.tag', [$tag3->id]);

    // $component->rows should have 2 items
    expect($component->rows->items())->toHaveCount(2);

    // Get Tag 4 from DB
    $tag4 = Tag::where('name', 'Tag 4')->first();

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
        'name' => 'Tag 6',
        'slug' => 'tag-6',
        'taxonomy' => 'tag',
    ]);

    // Apply Tag6 filter, set $filters['taxonomy']['tag'] to [$tag6->id]
    $component->set('filters.taxonomy.tag', [$tag6->id]);

    // $component->rows should have 0 items
    expect($component->rows->items())->toHaveCount(0);

    // Inspect SQL
    expect($component->rowsQuery->toSql())->toContain('select * from "posts" where exists (select * from "taxonomies" inner join "taxonomy_relations" on "taxonomies"."id" = "taxonomy_relations"."taxonomy_id" where "posts"."id" = "taxonomy_relations"."relatable_id" and "taxonomy_relations"."relatable_type" = ?');

    // First Binding should be TableTaxonomyFilterModel
    expect($component->rowsQuery->getBindings()[0])->toBe('TableTaxonomyFilterModel');

    // Second Binding should be $tag6->id
    expect($component->rowsQuery->getBindings()[1])->toBe($tag6->id);
});
