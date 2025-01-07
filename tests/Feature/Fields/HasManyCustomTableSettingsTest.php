<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Register the models with Aura
    Aura::fake();
    Aura::setModel(new Post);
    Aura::setModel(new HasManyCustomTableModel);
    Aura::registerRoutes('has-many-model');
    Aura::registerRoutes('post');
});

class HasManyCustomTableModel extends Resource
{
    public static ?string $slug = 'has-many-model';

    public static string $type = 'HasManyCustomTableModel';

    protected $fillable = ['type'];

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'slug' => 'posters',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => Post::class,
                'reverse' => false,
            ],

            [
                'name' => 'Posts',
                'slug' => 'posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'reverse' => true,
            ],
        ];
    }
}

test('search in hasMany does not lose query scope', function () {
    // Create 10 posts with different titles
    $testPosts = Post::factory()->count(5)->create(['title' => 'Test Post']);
    $otherPosts = Post::factory()->count(5)->create(['title' => 'Another Post']);

    $externalPosts = Post::factory()->count(5)->create(['title' => 'External Post']);

    $ids = array_merge($otherPosts->pluck('id')->toArray(), $testPosts->pluck('id')->toArray());

    // Create a model instance
    $instance = HasManyCustomTableModel::create([
        'posts' => $ids,
    ]);

    expect($instance->posts()->count())->toBe(10);

    // Get the field configuration from the model
    $field = collect(HasManyCustomTableModel::getFields())->firstWhere('slug', 'posts');

    // Test the table component directly
    $tableComponent = Livewire::test('aura::table', [
        'model' => app(Post::class),
        'field' => $field,
        'settings' => [
            'filters' => false,
            'global_filters' => false,
            'header_before' => false,
            'header_after' => false,
            'settings' => false,
            'search' => true,
        ],
        'parent' => $instance,
        'disabled' => true,
    ]);

    // Initial assertion - should see all posts
    $tableComponent->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 10;
    });

    // Search for 'Test'
    $tableComponent->set('search', 'Test');

    // Assert that we only see 5 posts (the ones with 'Test' in the title)
    $tableComponent->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 5 &&
            collect($rows->items())->every(fn ($post) => str_contains($post->title, 'Test'));
    });
});
