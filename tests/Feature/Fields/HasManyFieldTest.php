<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Re
class HasManyFieldModel extends Resource
{
    public static string $type = 'HasManyModel';

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

    // public function posts()
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }
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
    $model = HasManyFieldModel::create();

    // Create 3 posts
    $posts = Post::factory()->count(3)->create();

    // Create entries in post_relations table to mimic connections
    foreach ($posts as $post) {
        DB::table('post_relations')->insert([
            'resource_type' => HasManyFieldModel::class,
            'resource_id' => $model->id,
            'related_type' => Post::class,
            'related_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

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
        protected $table = 'custom_parents';

        protected $fillable = ['name', 'team_id', 'type'];

        public static $customTable = true;

        public function items()
        {
            return $this->hasMany(CustomChildModel::class, 'user_id');
        }
    }

    class CustomChildModel extends Resource
    {
        protected $fillable = ['parent_id', 'name', 'team_id', 'type'];

        public static $customTable = true;

        protected $table = 'custom_items';
    }

    $user = User::factory()->create();
    // Create a parent model
    $parentModel = CustomParentModel::create([
        'name' => 'Parent Model',
        'team_id' => 1,
        'type' => 'Resource',
    ]);

    // Create child items without the 'content' field
    for ($i = 1; $i <= 3; $i++) {
        CustomChildModel::create([
            'user_id' => $parentModel->id,
            'name' => "Item $i",
            'team_id' => 1,
            'type' => 'Resource',
        ]);
    }

    // Refresh the parent model to ensure relationships are loaded
    $parentModel = $parentModel->fresh();

    expect($parentModel->items()->count())->toBe(3);
    expect($parentModel->items()->first())->toBeInstanceOf(CustomChildModel::class);

    // Clean up: drop the custom table
    Schema::dropIfExists('custom_items');
    Schema::dropIfExists('custom_parents');
});
