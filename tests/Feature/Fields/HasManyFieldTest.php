<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Re
class HasManyFieldModel extends Resource
{
    public static string $type = 'HasManyModel';

    protected $fillable = ['type'];

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => Post::class,
                'slug' => 'posts',
            ],
        ];
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'related', 'post_relations', 'resource_id', 'related_id')
            ->withTimestamps()
            ->withPivot('resource_type', 'slug')
            ->wherePivot('related_type', Post::class)
            ->wherePivot('slug', 'posts');
    }
}

test('HasMany Field not shown in Create', function () {
    $model = new HasManyFieldModel;

    $createFields = $model->createFields();

    expect($createFields)->not->toContain(function ($field) {
        return $field['type'] === 'Aura\\Base\\Fields\\HasMany';
    });
});

test('HasMany Field shown on Edit', function () {
    $model = new HasManyFieldModel;

    $editFields = $model->editFields();

    $hasHasManyField = collect($editFields)->contains(function ($field) {
        return isset($field['field']) && $field['field'] instanceof \Aura\Base\Fields\HasMany;
    });

    expect($hasHasManyField)->toBeTrue();
});

test('HasMany query Meta Fields with posts table', function () {
    $model = HasManyFieldModel::create([
        'type' => 'HasManyModel',
    ]);

    // Create 3 posts
    $posts = Post::factory()->count(3)->create();

    // Create entries in post_relations table to mimic connections
    foreach ($posts as $post) {
        DB::table('post_relations')->insert([
            'resource_type' => HasManyFieldModel::class,
            'resource_id' => $model->id,
            'related_type' => Post::class,
            'related_id' => $post->id,
            'slug' => 'posts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Debug: Check the relationship query
    $query = $model->posts();
    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($model->posts()->count())->toBe(3);
    expect($model->posts()->first())->toBeInstanceOf(Post::class);
});

test('HasMany query with custom tables', function () {
    // Migration for custom_parents table
    Schema::create('custom_parents', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('team_id');
        $table->string('type');
        $table->timestamps();
        $table->foreignId('user_id')->nullable();
    });

    // Migration for custom_items table
    Schema::create('custom_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('parent_id');
        $table->string('name');
        $table->unsignedBigInteger('team_id');
        $table->string('type');
        $table->timestamps();
        $table->foreignId('user_id')->nullable();

        $table->foreign('parent_id')->references('id')->on('custom_parents');
    });

    // Define a CustomItem model for this test
    class CustomParentModel extends Resource
    {
        public static $customTable = true;

        protected $fillable = ['name', 'team_id', 'type'];

        protected $table = 'custom_parents';

        public static function getFields()
        {
            return [
                [
                    'name' => 'Items',
                    'type' => 'Aura\\Base\\Fields\\HasMany',
                    'column' => 'parent_id', // if you set a column, it will do a direct hasMany instead of polymorphic relationship
                    'resource' => CustomChildModel::class,
                    'slug' => 'items',
                ],
            ];
        }
    }

    class CustomChildModel extends Resource
    {
        public static $customTable = true;

        protected $fillable = ['parent_id', 'name', 'team_id', 'type'];

        protected $table = 'custom_items';
    }

    $user = User::factory()->create();
    // Create a parent model
    $parentModel = CustomParentModel::create([
        'name' => 'Parent Model',
        'team_id' => 1,
        'type' => 'Resource',
    ]);

    // Create child items
    for ($i = 1; $i <= 3; $i++) {
        CustomChildModel::create([
            'parent_id' => $parentModel->id,
            'name' => "Item $i",
            'team_id' => 1,
            'type' => 'Resource',
        ]);
    }

    // Refresh the parent model to ensure relationships are loaded
    $parentModel = $parentModel->fresh();

    expect($parentModel->items()->count())->toBe(3);
    expect($parentModel->items()->first())->toBeInstanceOf(CustomChildModel::class);

    // Clean up: drop the custom tables
    Schema::dropIfExists('custom_items');
    Schema::dropIfExists('custom_parents');
});
