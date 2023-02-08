<?php

use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
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

test('table default sorting', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    $post2 = Post::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
    ->assertSet('tableView', $post->defaultTableView())
    ->assertSet('perPage', $post->defaultPerPage())
    ->assertSet('columns', $post->getDefaultColumns());

    // $component->sorts should be []
    $this->assertEmpty($component->sorts);

    expect($component->rowsQuery->toSql())->toBe('select * from "posts" where "type" = ? and "team_id" = ? order by "id" desc limit 10 offset 0');

    // $compoent->rows->items() should be an array of posts
    $this->assertIsArray($component->rows->items());

    // First item should be the second post
    expect($component->rows->items()[0]->id)->toBe($post2->id);
    // Second item should be the first post
    expect($component->rows->items()[1]->id)->toBe($post->id);

    // Sort by id
    $component->call('sortBy', 'id');

    // $component->sorts should be ['id' => 'asc']
    $this->assertEquals(['id' => 'asc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);

    // $component->sorts should be ['id' => 'desc']
    $component->call('sortBy', 'id');

    $this->assertEquals(['id' => 'desc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post2->id);
    expect($component->rows->items()[1]->id)->toBe($post->id);

    // Sort by content
    $component->call('sortBy', 'content');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['content' => 'asc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);
});

// Create Resource for this test
class MetaSortingModel extends Post
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
                'name' => 'Number',
                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'number',
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

test('table sorting by meta field', function () {
    // Create a Posts
    $post = MetaSortingModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
    ]);

    $post2 = MetaSortingModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
    ]);

    expect($post->isMetaField('meta'))->toBeTrue();
    expect($post->isTaxonomyField('meta'))->toBeFalse();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
    ->assertSet('tableView', $post->defaultTableView())
    ->assertSet('perPage', $post->defaultPerPage())
    ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'meta');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['meta' => 'asc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post2->id);
    expect($component->rows->items()[1]->id)->toBe($post->id);

    // $component->sorts should be ['content' => 'desc']
    $component->call('sortBy', 'meta');

    $this->assertEquals(['meta' => 'desc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);

    // Inspect sql
    // SQL should contain: left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?
    expect($component->rowsQuery->toSql())->toContain('left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?');

    // Binding should be: ["meta","Post",1]
    expect($component->rowsQuery->getBindings()[0])->toBe('meta');
    expect($component->rowsQuery->getBindings()[1])->toBe('Post');
});

test('table sorting by meta field - number', function () {
    // Create a Posts
    $post = MetaSortingModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'number' => 10,
    ]);

    $post2 = MetaSortingModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'number' => 20,
    ]);

    $post3 = MetaSortingModel::create([
        'title' => 'Test Post 3',
        'content' => 'Test Content C',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'C',
        'number' => 100,
    ]);

    expect($post->isMetaField('number'))->toBeTrue();
    expect($post->isTaxonomyField('number'))->toBeFalse();
    expect($post->isNumberField('number'))->toBeTrue();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
    ->assertSet('tableView', $post->defaultTableView())
    ->assertSet('perPage', $post->defaultPerPage())
    ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'number');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['number' => 'asc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);
    expect($component->rows->items()[2]->id)->toBe($post3->id);

    // $component->sorts should be ['content' => 'desc']
    $component->call('sortBy', 'number');

    $this->assertEquals(['number' => 'desc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post3->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);
    expect($component->rows->items()[2]->id)->toBe($post->id);

    // Inspect sql
    // SQL should contain: left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?
    expect($component->rowsQuery->toSql())->toContain('CAST(post_meta.value AS SIGNED)');
});

test('table sorting by taxonomy field', function () {
    // Create a Posts
    $post = MetaSortingModel::create([
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

    $post2 = MetaSortingModel::create([
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

    expect($post->isTaxonomyField('tags'))->toBeTrue();
    expect($post->isMetaField('tags'))->toBeFalse();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
    ->assertSet('tableView', $post->defaultTableView())
    ->assertSet('perPage', $post->defaultPerPage())
    ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'tags');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['tags' => 'asc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post->id);
    expect($component->rows->items()[1]->id)->toBe($post2->id);

    // $component->sorts should be ['content' => 'desc']
    $component->call('sortBy', 'tags');

    $this->assertEquals(['tags' => 'desc'], $component->sorts);

    expect($component->rows->items()[0]->id)->toBe($post2->id);
    expect($component->rows->items()[1]->id)->toBe($post->id);

    // Inspect sql
    // SQL should contain left join
    expect($component->rowsQuery->toSql())->toContain('select "posts".*, (select "name" from "taxonomies" left join "taxonomy_relations" on "taxonomies"."id" = "taxonomy_relations"."taxonomy_id" and "taxonomy_relations"."relatable_type" = ? where "taxonomy" = ? and "relatable_id" = "posts"."id" and "team_id" = ? order by "name" asc limit 1) as "first_taxonomy" from "posts" where "type" = ?');

    // Binding should be: ["meta","Post",1]
    expect($component->rowsQuery->getBindings()[0])->toBe('MetaSortingModel');
    expect($component->rowsQuery->getBindings()[1])->toBe('Tag');
});
