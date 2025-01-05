<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View as ResourceView;

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
    protected static ?string $slug = 'has-many-model';

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
    public static string $type = 'hasmanymodel';
    protected static ?string $slug = 'hasmanymodel';

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

    protected static ?string $slug = 'has-many-model';

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
    protected static ?string $slug = 'has-many-model';

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
    $model = HasManyFieldViewsModelCustomView::create([]);
    $post = Post::first();
    // Create test posts
    $model->fields = [
        'posts' => [$post->id],
    ];

    $model->save();

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelCustomView());

    // Test the view component
    $component = Livewire::test(ResourceView::class, ['id' => $model->id])
        ->assertSee('custom-post-view');
        
    // Assert view_view is used
    $field = $model->getFields()[0];
    expect($field['view_view'])->toBe('custom.post-view');
});

test('thumbnail field is properly handled in index view', function () {
    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelThumbnail);

    $model = HasManyFieldViewsModelThumbnail::create([]);
    $post = Post::first();
    
    // Create a post with an image
    $post->image = ['image1.jpg'];
    $post->save();

    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    // Test the index view
    Livewire::test(Index::class)
        ->assertSee('image1.jpg');

    // Assert thumbnail field is set
    $field = $model->getFields()[0];
    expect($field['thumbnail'])->toBe('image');
});

test('custom view_index renders correctly', function () {

    $this->withoutExceptionHandling();

    $model = HasManyFieldViewsModelCustomIndex::create([]);
    $post = Post::first();
    
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    ray('here', $model);

    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelCustomIndex);

    // Test the index view
    Livewire::test(Index::class)
        ->assertSee('custom-post-index');

    // Assert view_index is used
    $field = $model->getFields()[0];
    expect($field['view_index'])->toBe('custom.post-index');
});

test('default view rendering when no custom views are specified', function () {
    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModel);

    $model = HasManyFieldViewsModel::create([]);
    $post = Post::first();
    
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    // Test index view
    Livewire::test(Index::class)
        ->assertSee('text-gray-800');

    // Test view component
    Livewire::test(ResourceView::class, ['id' => $model->id])
        ->assertSee('text-gray-800');
});

test('thumbnail with missing image shows placeholder', function () {
    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModelThumbnail);

    $model = HasManyFieldViewsModelThumbnail::create([]);
    $post = Post::first();
    
    $model->fields = [
        'posts' => [$post->id],
    ];
    $model->save();

    // Test the index view
    Livewire::test(Index::class)
        ->assertSee('bg-gray-300');
});

test('multiple items render correctly in index view', function () {
    Aura::fake();
    Aura::setModel(new HasManyFieldViewsModel);

    $model = HasManyFieldViewsModel::create([]);
    $posts = Post::take(2)->get();
    
    $model->fields = [
        'posts' => $posts->pluck('id')->toArray(),
    ];
    $model->save();

    // Test the index view
    Livewire::test(Index::class)
        ->assertSee('flex space-x-2');
});
