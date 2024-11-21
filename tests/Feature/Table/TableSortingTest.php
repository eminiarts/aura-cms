<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new MetaSortingModel);
});


// Create Resource for this test
class MetaSortingModel extends Resource
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
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'number',
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
        ->assertSet('settings.default_view', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());

    // $component->sorts should be []
    $this->assertEmpty($component->sorts);

    // expect($component->rowsQuery->toSql())->toBe('select * from "posts" where "posts"."type" = ? and "posts"."team_id" = ? order by "posts"."id" desc limit 10 offset 0');

    // $compoent->rows->items() should be an array of posts
    $component->assertViewHas('rows', function ($rows) {
        return is_array($rows->items());
    });

    // First item should be the second post
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[0]->id === $post2->id;
    });
    // Second item should be the first post
    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[1]->id === $post->id;
    });

    // Sort by id
    $component->call('sortBy', 'id');

    // $component->sorts should be ['id' => 'asc']
    $this->assertEquals(['id' => 'asc'], $component->sorts);

    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[0]->id === $post->id;
    });
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[1]->id === $post2->id;
    });

    // $component->sorts should be ['id' => 'desc']
    $component->call('sortBy', 'id');

    $this->assertEquals(['id' => 'desc'], $component->sorts);

    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[0]->id === $post2->id;
    });
    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[1]->id === $post->id;
    });

    // Sort by content
    $component->call('sortBy', 'content');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['content' => 'asc'], $component->sorts);

    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[0]->id === $post->id;
    });
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[1]->id === $post2->id;
    });
});


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
        ->assertSet('settings.default_view', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'meta');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['meta' => 'asc'], $component->sorts);

    $component->assertViewHas('rows', function ($rows) use ($post2, $post) {
        return $rows->items()[0]->id === $post2->id && $rows->items()[1]->id === $post->id;
    });

    // $component->sorts should be ['content' => 'desc']
    $component->call('sortBy', 'meta');

    $this->assertEquals(['meta' => 'desc'], $component->sorts);

    $component->assertViewHas('rows', function ($rows) use ($post, $post2) {
        return $rows->items()[0]->id === $post->id && $rows->items()[1]->id === $post2->id;
    });

    // Inspect sql
    // SQL should contain: left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?
    // expect($component->rowsQuery->toSql())->toContain('left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?');

    // // Binding should be: ["meta","Post",1]
    // expect($component->rowsQuery->getBindings()[0])->toBe('meta');
    // expect($component->rowsQuery->getBindings()[1])->toBe('Post');
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
        ->assertSet('settings.default_view', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'number');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['number' => 'asc'], $component->sorts);

    // Assert that the first item is the first post
    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[0]->id === $post->id;
    });
    // Assert that the second item is the second post
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[1]->id === $post2->id;
    });
    // Assert that the third item is the third post
    $component->assertViewHas('rows', function ($rows) use ($post3) {
        return $rows->items()[2]->id === $post3->id;
    });

    // $component->sorts should be ['number' => 'desc']
    $component->call('sortBy', 'number');

    $this->assertEquals(['number' => 'desc'], $component->sorts);

    // Assert that the first item is the third post after sorting by number in descending order
    $component->assertViewHas('rows', function ($rows) use ($post3) {
        return $rows->items()[0]->id === $post3->id;
    });
    // Assert that the second item is the second post after sorting by number in descending order
    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return $rows->items()[1]->id === $post2->id;
    });
    // Assert that the third item is the first post after sorting by number in descending order
    $component->assertViewHas('rows', function ($rows) use ($post) {
        return $rows->items()[2]->id === $post->id;
    });

    // Inspect sql
    // SQL should contain: left join "post_meta" on "posts"."id" = "post_meta"."post_id" and "post_meta"."key" = ?
    // expect($component->rowsQuery->toSql())->toContain('CAST(post_meta.value AS DECIMAL(10,2))');
});

test('table sorting by taxonomy field', function () {
    // Create a Posts
    $post = MetaSortingModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'B',
        'tags' => [
            'Tag 1', 'Tag 2', 'Tag 3',
        ],
    ]);

    $post2 = MetaSortingModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'meta' => 'A',
        'tags' => [
            'Tag 3', 'Tag 4', 'Tag 5',
        ],
    ]);

    expect($post->isTaxonomyField('tags'))->toBeTrue();
    expect($post->isMetaField('tags'))->toBeTrue();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
        ->assertSet('settings.default_view', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());

    // Sort by content
    $component->call('sortBy', 'tags');

    // $component->sorts should be ['content' => 'asc']
    $this->assertEquals(['tags' => 'asc'], $component->sorts);

    $query = $component->get('rowsQuery');

    ray($component);
    // dd($query, 'hier');
    $rows = $query->get();
    expect($rows[0]->id)->toBe($post->id);
    expect($rows[1]->id)->toBe($post2->id);

    // $component->sorts should be ['content' => 'desc']
    $component->call('sortBy', 'tags');

    $this->assertEquals(['tags' => 'desc'], $component->sorts);

    $query = $component->get('rowsQuery');

    // dd('hier');
    // dd($query);
    $rows = $query->get();
    expect($rows[0]->id)->toBe($post2->id);
    expect($rows[1]->id)->toBe($post->id);

    // SQL should contain left join
    expect($query->toSql())->toContain('left join "post_relations" as "pr" on "posts"."id" = "pr"."related_id"');

    // Binding should be: ["meta","Post",1]
    expect($query->getBindings()[0])->toBe('MetaSortingModel');
});
