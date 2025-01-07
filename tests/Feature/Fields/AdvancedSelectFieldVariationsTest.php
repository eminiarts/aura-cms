<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Post::factory(3)->create();
});

class HasManyFieldOptionsModel extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
            ],
        ];
    }
}

class HasManyFieldOptionsModel2 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'multiple' => false,
            ],
        ];
    }
}
class HasManyFieldOptionsModel3 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'multiple' => true,
            ],
        ];
    }
}

class HasManyFieldOptionsModel4 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'multiple' => false,
                'polymorphic_relation' => true,
            ],
        ];
    }
}

class HasManyFieldOptionsModel5 extends Resource
{
    use \Aura\Base\Traits\SaveMetaFields;

    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'multiple' => true,
                'polymorphic_relation' => false,
            ],
        ];
    }
}

class HasManyFieldOptionsModel6 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'reverse' => true,
            ],
        ];
    }
}

class HasManyFieldOptionsModel7 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'polymorphic_relation' => false,
                'reverse' => true,
            ],
        ];
    }
}

class HasManyFieldOptionsModel8 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'polymorphic_relation' => false,
                'return_type' => 'id',
            ],
        ];
    }
}

class HasManyFieldOptionsModel9 extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
                'polymorphic_relation' => false,
                'return_type' => 'object',
            ],
        ];
    }
}

test('HasMany relation is working correctly', function () {
    $model = new HasManyFieldOptionsModel;

    // Expect 3 posts in DB
    expect(Post::count())->toBe(3);

    // Save the model
    $model->posts = [1, 2, 3];
    $model->save();

    // Get relations from DB
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->orderBy('order')
        ->get();

    // Verify total count
    expect($relations)->toHaveCount(3);

    // Verify each relation's properties
    expect($relations[0])->toMatchArray([
        'resource_type' => 'Tests\Feature\Livewire\HasManyFieldOptionsModel',
        'related_type' => 'Aura\Base\Tests\Resources\Post',
        'related_id' => 1,
        'order' => 1,
        'slug' => 'posts',
    ]);

    expect($relations[1])->toMatchArray([
        'resource_type' => 'Tests\Feature\Livewire\HasManyFieldOptionsModel',
        'related_type' => 'Aura\Base\Tests\Resources\Post',
        'related_id' => 2,
        'order' => 2,
        'slug' => 'posts',
    ]);

    expect($relations[2])->toMatchArray([
        'resource_type' => 'Tests\Feature\Livewire\HasManyFieldOptionsModel',
        'related_type' => 'Aura\Base\Tests\Resources\Post',
        'related_id' => 3,
        'order' => 3,
        'slug' => 'posts',
    ]);

    // Verify timestamps are set
    $relations->each(fn ($relation) => expect($relation->created_at)->not->toBeNull()
        ->and($relation->updated_at)->not->toBeNull()
    );
});

test('HasMany relation respects custom order of relations', function () {
    $model = new HasManyFieldOptionsModel;

    // Save posts in reverse order
    $model->posts = [3, 2, 1];
    $model->save();

    // Get relations from DB
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->orderBy('order')
        ->get();

    // Verify total count
    expect($relations)->toHaveCount(3);

    // Verify order matches input order
    expect($relations[0]->related_id)->toBe(3)
        ->and($relations[0]->order)->toBe(1);
    expect($relations[1]->related_id)->toBe(2)
        ->and($relations[1]->order)->toBe(2);
    expect($relations[2]->related_id)->toBe(1)
        ->and($relations[2]->order)->toBe(3);

    // Verify the model relation returns posts in correct order
    $posts = $model->posts;
    expect($posts)->toHaveCount(3);
    expect($posts->pluck('id')->toArray())->toBe([3, 2, 1]);
});

test('HasMany relation updates correctly when relations change', function () {
    $model = new HasManyFieldOptionsModel;

    // First save with 3 posts
    $model->posts = [1, 2, 3];
    $model->save();

    // Verify initial state
    expect($model->posts)->toHaveCount(3);
    expect(DB::table('post_relations')->where('resource_id', $model->id)->count())->toBe(3);

    // Update to only have 1 post
    $model->posts = [2];
    $model->save();

    // Verify final state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(2)
        ->and($relations[0]->order)->toBe(1);

    // Verify through model relation
    expect($model->posts)->toHaveCount(1);
    expect($model->posts->first()->id)->toBe(2);

    // Verify old relations were deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->whereIn('related_id', [1, 3])
        ->count()
    )->toBe(0);
});

test('Single relation (multiple => false) saves and retrieves correctly', function () {
    $model = new HasManyFieldOptionsModel2;

    // Save single post - wrap in array since the field might expect it internally
    $model->posts = [2];
    $model->save();

    // Verify database state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    // Should only have one relation
    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(2)
        ->and($relations[0]->order)->toBe(1);

    // Verify through model relation
    expect($model->posts)->not->toBeNull();

    // dd($model->posts);
    expect($model->posts->id)->toBe(2);

    // Update to different post
    $model->posts = [3];
    $model->save();

    // Verify updated state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(3)
        ->and($relations[0]->order)->toBe(1);

    // Verify old relation was deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->where('related_id', 2)
        ->count()
    )->toBe(0);

    // Verify through model relation after update
    expect($model->posts->id)->toBe(3);

    // Test setting to null
    $model->posts = [];
    $model->save();

    // Verify all relations are deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->count()
    )->toBe(0);

    // Verify model relation is null
    expect($model->posts)->toBeEmpty();
});

test('Multiple relation (multiple => true) behaves same as original implementation', function () {
    $model = new HasManyFieldOptionsModel3;

    // Save multiple posts
    $model->posts = [1, 2, 3];
    $model->save();

    // Verify database state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->orderBy('order')
        ->get();

    // Should have all three relations
    expect($relations)->toHaveCount(3);
    expect($relations[0]->related_id)->toBe(1)
        ->and($relations[0]->order)->toBe(1);
    expect($relations[1]->related_id)->toBe(2)
        ->and($relations[1]->order)->toBe(2);
    expect($relations[2]->related_id)->toBe(3)
        ->and($relations[2]->order)->toBe(3);

    // Verify through model relation
    expect($model->posts)->toHaveCount(3);
    expect($model->posts->pluck('id')->toArray())->toBe([1, 2, 3]);

    // Update to different posts
    $model->posts = [2];
    $model->save();

    // Verify updated state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(2)
        ->and($relations[0]->order)->toBe(1);

    // Verify old relations were deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->whereIn('related_id', [1, 3])
        ->count()
    )->toBe(0);

    // Verify through model relation after update
    expect($model->posts)->toHaveCount(1);
    expect($model->posts->first()->id)->toBe(2);
});

test('Single relation (multiple => false) saves int only', function () {
    $model = new HasManyFieldOptionsModel2;

    // Save single post - wrap in array since the field might expect it internally
    $model->posts = 2;
    $model->save();

    // Verify database state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    // Should only have one relation
    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(2)
        ->and($relations[0]->order)->toBe(1);

    // Verify through model relation
    expect($model->posts)->not->toBeNull();

    // dd($model->posts);
    expect($model->posts->id)->toBe(2);

    // Update to different post
    $model->posts = [3];
    $model->save();

    // Verify updated state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(3)
        ->and($relations[0]->order)->toBe(1);

    // Verify old relation was deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->where('related_id', 2)
        ->count()
    )->toBe(0);

    // Verify through model relation after update
    expect($model->posts->id)->toBe(3);

    // Test setting to null
    $model->posts = [];
    $model->save();

    // Verify all relations are deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->count()
    )->toBe(0);

    // Verify model relation is null
    expect($model->posts)->toBeEmpty();
});

test('Single polymorphic relation (multiple => false, polymorphic => true) works correctly', function () {
    $model = new HasManyFieldOptionsModel4;

    // Save single post
    $model->posts = 2;
    $model->save();

    // Verify database state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    // Should only have one relation
    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(2)
        ->and($relations[0]->order)->toBe(1)
        ->and($relations[0]->related_type)->toBe(Post::class);

    // Verify through model relation
    expect($model->posts)->not->toBeNull();
    expect($model->posts->id)->toBe(2);

    // Update to different post
    $model->posts = 3;
    $model->save();

    // Verify updated state
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->get();

    expect($relations)->toHaveCount(1);
    expect($relations[0]->related_id)->toBe(3)
        ->and($relations[0]->order)->toBe(1)
        ->and($relations[0]->related_type)->toBe(Post::class);

    // Verify old relation was deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->where('related_id', 2)
        ->count()
    )->toBe(0);

    // Test setting to empty
    $model->posts = [];
    $model->save();

    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->count()
    )->toBe(0);
});

test('Multiple meta field (multiple => true, polymorphic => false) saves as meta', function () {
    $model = new HasManyFieldOptionsModel5;

    // Save multiple posts
    $model->posts = [1, 2, 3];
    $model->save();

    // Should save in meta table instead of relations
    $meta = DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('metable_type', HasManyFieldOptionsModel5::class)
        ->where('key', 'posts')
        ->first();

    expect($meta)->not->toBeNull();

    // Meta value should be JSON array of IDs
    $value = json_decode($meta->value, true);
    expect($value)->toBe([1, 2, 3]);

    // Verify through model accessor
    expect($model->posts)->toHaveCount(3);
    expect($model->posts->pluck('id')->toArray())->toBe([1, 2, 3]);

    // Update to single post
    $model->posts = [2];
    $model->save();

    // Verify meta was updated
    $meta = DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('metable_type', HasManyFieldOptionsModel5::class)
        ->where('key', 'posts')
        ->first();

    $value = json_decode($meta->value, true);
    expect($value)->toBe([2]);

    // Verify through model
    expect($model->posts)->toHaveCount(1);
    expect($model->posts->first()->id)->toBe(2);

    // Test setting to empty
    $model->posts = [];
    $model->save();

    // Meta should be deleted or set to empty array
    $meta = DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('metable_type', HasManyFieldOptionsModel5::class)
        ->where('key', 'posts')
        ->first();

    expect(json_decode($meta->value))->toBeEmpty();
    expect($model->posts)->toBeEmpty();
});

test('reverse polymorphic relation saves and retrieves correctly', function () {
    $model = HasManyFieldOptionsModel6::create(['type' => 'test']);

    // Save the relation
    $model->fields = [
        'posts' => [1, 2, 3],
    ];
    $model->save();

    // Check relations were saved correctly
    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->where('resource_type', HasManyFieldOptionsModel6::class)
        ->where('slug', 'posts')
        ->orderBy('order')
        ->get();

    expect($relations)->toHaveCount(0);

    // Check relations were saved correctly
    $relations = DB::table('post_relations')
        ->where('related_id', $model->id)
        ->where('related_type', HasManyFieldOptionsModel6::class)
        ->where('slug', 'posts')
        ->orderBy('order')
        ->get();

    expect($relations)->toHaveCount(3);

    expect($relations->pluck('resource_id')->toArray())->toBe([1, 2, 3]);
    expect($relations->pluck('resource_type')->unique()->first())->toBe(Post::class);

    // Check order is preserved
    expect($relations->pluck('order')->toArray())->toBe([1, 2, 3]);

    // Check we can retrieve the relation from both sides
    $model->refresh();

    expect($model->posts)->toHaveCount(3);

    expect($model->posts->pluck('id')->toArray())->toBe([1, 2, 3]);

    // Test updating relations
    $model->fields = [
        'posts' => [2, 3], // Remove post 1
    ];
    $model->save();

    $relations = DB::table('post_relations')
        ->where('related_id', $model->id)
        ->where('related_type', HasManyFieldOptionsModel6::class)
        ->where('slug', 'posts')
        ->orderBy('order')
        ->get();

    expect($relations)->toHaveCount(2);
    expect($relations->pluck('resource_id')->toArray())->toBe([2, 3]);

    // Check the removed relation is gone from both sides
    $model->refresh();
    expect($model->posts)->toHaveCount(2);
    expect($model->posts->pluck('id')->toArray())->toBe([2, 3]);

    // Test clearing relations
    $model->fields = [
        'posts' => [],
    ];
    $model->save();

    $relations = DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->where('resource_type', HasManyFieldOptionsModel6::class)
        ->where('slug', 'posts')
        ->get();

    expect($relations)->toHaveCount(0);
});

test('reverse with polymorphic_relation = false should be ignored', function () {
    $model = HasManyFieldOptionsModel7::create(['type' => 'test']);

    // Save the relation
    $model->fields = [
        'posts' => [1, 2, 3],
    ];
    $model->save();

    // Verify data was saved in meta table
    $meta = $model->meta()->where('key', 'posts')->first();
    expect($meta)->not->toBeNull();
    expect(json_decode($meta->value))->toBe([1, 2, 3]);

    // Verify no relations were created in post_relations table
    $relations = DB::table('post_relations')
        ->where('related_id', $model->id)
        ->where('related_type', HasManyFieldOptionsModel7::class)
        ->where('slug', 'posts')
        ->get();

    expect($relations)->toHaveCount(0);

    // Verify we can retrieve the values
    $model->refresh();
    expect($model->fields['posts'])->toBe([1, 2, 3]);

    // Test updating relations
    $model->fields = [
        'posts' => [2, 3],
    ];
    $model->save();

    $meta = $model->meta()->where('key', 'posts')->first();
    expect(json_decode($meta->value))->toBe([2, 3]);

    // Test clearing relations
    $model->fields = [
        'posts' => [],
    ];
    $model->save();

    $meta = $model->meta()->where('key', 'posts')->first();
    expect(json_decode($meta->value))->toBe([]);
});

test('return_type = id should return ids instead of objects', function () {
    $model = HasManyFieldOptionsModel8::create(['type' => 'test']);

    // Save the relation
    $model->fields = [
        'posts' => [1, 2, 3],
    ];
    $model->save();

    // Verify data was saved in meta table
    $meta = $model->meta()->where('key', 'posts')->first();
    expect($meta)->not->toBeNull();
    expect(json_decode($meta->value))->toBe([1, 2, 3]);

    // Verify that posts returns array of ids instead of objects
    $model->refresh();
    expect($model->posts)->toBe([1, 2, 3]);

    // Test updating relations
    $model->fields = [
        'posts' => [2, 3],
    ];
    $model->save();

    $model->refresh();
    expect($model->posts)->toBe([2, 3]);

    // Test clearing relations
    $model->fields = [
        'posts' => [],
    ];
    $model->save();

    $model->refresh();
    expect($model->posts)->toBeEmpty();
});

test('return_type = object should return model objects', function () {
    $model = HasManyFieldOptionsModel9::create(['type' => 'test']);

    // Save the relation
    $model->fields = [
        'posts' => [1, 2, 3],
    ];
    $model->save();

    // Verify data was saved in meta table
    $meta = $model->meta()->where('key', 'posts')->first();
    expect($meta)->not->toBeNull();
    expect(json_decode($meta->value))->toBe([1, 2, 3]);

    // Verify that posts returns Post objects
    $model->refresh();
    expect($model->posts)->toHaveCount(3);
    expect($model->posts)->each->toBeInstanceOf(Post::class);
    expect($model->posts->pluck('id')->toArray())->toBe([1, 2, 3]);

    // Test updating relations
    $model->fields = [
        'posts' => [2, 3],
    ];
    $model->save();

    $model->refresh();
    expect($model->posts)->toHaveCount(2);
    expect($model->posts)->each->toBeInstanceOf(Post::class);
    expect($model->posts->pluck('id')->toArray())->toBe([2, 3]);

    // Test clearing relations
    $model->fields = [
        'posts' => [],
    ];
    $model->save();

    $model->refresh();
    expect($model->posts)->toBeEmpty();
});
