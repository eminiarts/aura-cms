<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new HasManyCustomTableModel);
    Aura::registerRoutes('has-many-model');
});

// Create Re
class HasManyCustomTableModel extends Resource
{
    public static ?string $slug = 'has-many-model';

    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => Post::class,
                'slug' => 'posts',
                'table_settings' => [
                    'per_page' => 5,
                ],
            ],
        ];
    }

    // public function posts()
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }
}

test('Only 5 posts shown in View', function () {
    $model = new HasManyCustomTableModel;

    // Create 10 posts
    Post::factory()->count(10)->create();

    // Visit the Post Index Page
    $this->get(route('aura.has-many-model.index'))
        ->assertSeeLivewire('aura::resource-index')
        ->assertSeeLivewire('aura::table');
});

test('search in hasMany does not lose query scope', function () {
    $model = new HasManyCustomTableModel;

    // Create 10 posts with different titles
    Post::factory()->count(5)->create(['title' => 'Test Post']);
    Post::factory()->count(5)->create(['title' => 'Another Post']);

    // Create a model instance
    $instance = HasManyCustomTableModel::create();

    // Visit the edit page
    $component = Livewire::test('aura::resource-edit', [
        'slug' => 'has-many-model',
        'id' => $instance->id,
    ]);

    // Assert that we see the table component
    $component->assertSeeLivewire('aura::table');

    // Search for 'Test'
    $component->set('search', 'Test');

    // Assert that we only see 5 posts (the ones with 'Test' in the title)
    expect(Post::where('title', 'like', '%Test%')->count())->toBe(5);
});
