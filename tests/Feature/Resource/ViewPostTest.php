<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
});

test('post can be viewed', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

    // Visit the Post Index Page
    // Use class reference for Livewire 3.x compatibility with full-page components
    $this->get(route('aura.post.view', [$post->id]))
        ->assertSeeLivewire(View::class)
        ->assertSee('Test Post');
});

test('post view - view fields are displayed correctly', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    Aura::fake();
    Aura::setModel(new Post);

    // LiveWire Component
    $component = livewire('aura::resource-view', [$post->id]);

    // Expect $component->viewFields to be an array
    expect($component->viewFields)->toBeArray();
});

test('resource view - can be customized via viewView method', function () {
    // Define a custom resource for this test
    class CustomViewResource extends Resource
    {
        public static string $type = 'CustomViewResource';

        protected static ?string $slug = 'custom-resource';

        public static function getFields()
        {
            return [
                [
                    'name' => 'Title',
                    'type' => 'Aura\\Base\\Fields\\Text',
                    'slug' => 'title',
                ],
                [
                    'name' => 'Content',
                    'type' => 'Aura\\Base\\Fields\\Textarea',
                    'slug' => 'content',
                ],
            ];
        }

        public function viewView()
        {
            return 'custom.resource.view';
        }
    }

    Aura::registerResources([
        CustomViewResource::class,
    ]);

    // Create the custom resource
    $customResource = CustomViewResource::create([
        'title' => 'Custom Resource Title',
        'content' => 'Custom Resource Content',
        'team_id' => 1,
        'type' => 'CustomViewResource',
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'CustomViewResource']);

    // Create a custom view file
    $customViewPath = resource_path('views/custom/resource/view.blade.php');
    $customViewContent = '<div>Custom Resource View: {{ $model->title }}</div>';

    // Ensure the directory exists
    if (! file_exists(dirname($customViewPath))) {
        mkdir(dirname($customViewPath), 0777, true);
    }

    file_put_contents($customViewPath, $customViewContent);

    Aura::fake();
    Aura::setModel(new CustomViewResource);

    Livewire::test(View::class, ['slug' => 'custom-resource', 'id' => $customResource->id])->assertSee('Custom Resource View: Custom Resource Title');

    // Clean up: remove the temporary view file
    unlink($customViewPath);
    rmdir(dirname($customViewPath));
    rmdir(dirname(dirname($customViewPath)));
});

test('post with special characters in title can be viewed', function () {
    // Create a Post with special characters
    $post = Post::create([
        'title' => 'Test Post with <special> & "characters"',
        'content' => 'Test Content with special chars: <>&"\'',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post with <special> & "characters"']);

    // Visit the Post View Page - should properly escape special characters
    $this->get(route('aura.post.view', [$post->id]))
        ->assertSeeLivewire(View::class)
        ->assertOk();
});

test('viewing non-existent post returns error', function () {
    // Try to view a post that does not exist
    // The system returns 403 because authorization check fails when model is null
    $nonExistentId = 99999;

    $this->get(route('aura.post.view', [$nonExistentId]))
        ->assertForbidden();
});

test('post view displays content correctly', function () {
    // Create a Post with specific content
    $post = Post::create([
        'title' => 'Post Title for Content Test',
        'content' => 'This is the detailed content of the post.',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Visit the Post View Page and verify content is displayed
    $this->get(route('aura.post.view', [$post->id]))
        ->assertSeeLivewire(View::class)
        ->assertSee('Post Title for Content Test')
        ->assertOk();
});
