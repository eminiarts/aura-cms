<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Post::factory(3)->create();
});

class HasManyFieldOptionsModel extends Resource
{
    public static string $type = 'HasManyModel';

    protected $fillable = ['type'];

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

    protected $fillable = ['type'];

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

    protected $fillable = ['type'];

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
    $relations->each(fn ($relation) => 
        expect($relation->created_at)->not->toBeNull()
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
    $model->posts = null;
    $model->save();

    // Verify all relations are deleted
    expect(DB::table('post_relations')
        ->where('resource_id', $model->id)
        ->count()
    )->toBe(0);

    // Verify model relation is null
    expect($model->posts)->toBeNull();
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
