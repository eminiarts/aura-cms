<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Aura\Base\Facades\Aura;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Post::factory(3)->create();


    // Create custom views directory
    if (!File::exists(resource_path('views/custom'))) {
        File::makeDirectory(resource_path('views/custom'), 0755, true);
    }

    // Create custom post view template
    File::put(
        resource_path('views/custom/post-view.blade.php'),
        '<div class="custom-post-view">{{ $item->title() }}</div>'
    );

    // Create custom post index template
    File::put(
        resource_path('views/custom/post-index.blade.php'),
        '<div class="custom-post-index">{{ $item->title() }}</div>'
    );

    View::addLocation(resource_path('views'));
});

afterEach(function () {
    // Clean up custom views
    if (File::exists(resource_path('views/custom'))) {
        File::deleteDirectory(resource_path('views/custom'));
    }
});

class HasManyFieldViewsModel extends Resource
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

class HasManyFieldViewsModelCustomView extends Resource
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
                'view_view' => 'custom.post-view',
            ],
        ];
    }
}

class HasManyFieldViewsModelThumbnail extends Resource
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
                'thumbnail' => 'image',
            ],
        ];
    }
}

class HasManyFieldViewsModelCustomIndex extends Resource
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
                'view_index' => 'custom.post-index',
            ],
        ];
    }
}

test('custom view_view renders correctly', function () {
   
    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelCustomView);

    $model = HasManyFieldViewsModelCustomView::create(['type' => 'test']);
    $post = Post::first();
    
    // Create test posts
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();


    // Create a view component
    $this->get("/admin/test/{$model->id}")
        ->assertSuccessful()
        ->assertSee('custom-post-view');

    // Assert view_view is used
    $field = $model->getFields()[0];
    expect($field['view_view'])->toBe('custom.post-view');
});

test('thumbnail field is properly handled in index view', function () {
    $model = HasManyFieldViewsModelThumbnail::create(['type' => 'test']);
    $post = Post::first();
    
    // Create a post with an image
    $post->image = ['image1.jpg'];
    $post->save();

    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelThumbnail);

    // Test the index view
    $this->get('/admin/test')
        ->assertSuccessful()
        ->assertSee('image1.jpg');

    // Assert thumbnail field is set
    $field = $model->getFields()[0];
    expect($field['thumbnail'])->toBe('image');
});

test('custom view_index renders correctly', function () {
    $model = HasManyFieldViewsModelCustomIndex::create(['type' => 'test']);
    $post = Post::first();
    
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelCustomIndex);

    // Test the index view
    $this->get('/admin/test')
        ->assertSuccessful()
        ->assertSee('custom-post-index');

    // Assert view_index is used
    $field = $model->getFields()[0];
    expect($field['view_index'])->toBe('custom.post-index');
});

test('default view rendering when no custom views are specified', function () {
    $model = HasManyFieldViewsModel::create(['type' => 'test']);
    $post = Post::first();
    
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModel);

    // Test index view
    $this->get('/admin/test')
        ->assertSuccessful()
        ->assertSee('text-gray-800');

    // Test view component
    $this->get("/admin/test/{$model->id}")
        ->assertSuccessful()
        ->assertSee('truncate');
});

test('thumbnail with missing image shows placeholder', function () {
    $model = HasManyFieldViewsModelThumbnail::create(['type' => 'test']);
    $post = Post::first();
    
    // Create a post without an image
    $post->image = null;
    $post->save();

    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelThumbnail);

    // Test the index view
    $this->get('/admin/test')
        ->assertSuccessful()
        ->assertSee('bg-gray-300');
});

test('multiple items render correctly in index view', function () {
    $model = HasManyFieldViewsModel::create(['type' => 'test']);
    $posts = Post::take(3)->get();
    
    // Create multiple posts
    $model->fields = [
        'posts' => $posts->pluck('id')->toArray(),
    ];
    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModel);

    // Test the index view
    $this->get('/admin/test')
        ->assertSuccessful()
        ->assertSee('flex space-x-2');
});
