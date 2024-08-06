<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    expect($editFields)->toContain(function ($field) {
        return $field['type'] === 'Aura\\Base\\Fields\\HasMany';
    });
});

test('HasMany query Meta Fields with posts table', function () {
    $user = User::factory()->create();
    $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

    $model = new HasManyFieldModel;
    $model->id = $user->id;

    expect($model->posts()->count())->toBe(3);
    expect($model->posts()->first())->toBeInstanceOf(Post::class);
});

test('HasMany query with custom tables', function () {
    // Create a custom table for this test
    Schema::create('custom_items', function ($table) {
        $table->id();
        $table->foreignId('user_id');
        $table->string('name');
        $table->timestamps();
    });

    // Define a CustomItem model for this test
    class CustomItem extends Resource
    {
        protected $fillable = ['user_id', 'name'];

        protected $table = 'custom_items';
    }

    // Define a model with HasMany relationship to CustomItem
    class CustomTableHasManyFieldModel extends Resource
    {
        public static string $type = 'CustomTableHasManyModel';

        public function customItems()
        {
            return $this->hasMany(CustomItem::class, 'user_id');
        }

        public static function getFields()
        {
            return [
                [
                    'name' => 'Custom Items',
                    'type' => 'Aura\\Base\\Fields\\HasMany',
                    'resource' => CustomItem::class,
                    'slug' => 'customItems',
                ],
            ];
        }
    }

    $user = User::factory()->create();
    $customItems = collect([
        ['user_id' => $user->id, 'name' => 'Item 1'],
        ['user_id' => $user->id, 'name' => 'Item 2'],
        ['user_id' => $user->id, 'name' => 'Item 3'],
    ])->map(function ($item) {
        return CustomItem::create($item);
    });

    $model = new CustomTableHasManyFieldModel;
    $model->id = $user->id;

    expect($model->customItems()->count())->toBe(3);
    expect($model->customItems()->first())->toBeInstanceOf(CustomItem::class);

    // Clean up: drop the custom table
    Schema::dropIfExists('custom_items');
});
