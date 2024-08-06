<?php

use Livewire\Livewire;

use Aura\Base\Resource;
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Post;
use Illuminate\Support\Facades\DB;
use function Pest\Livewire\livewire;
use Aura\Base\Livewire\Resource\View;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
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
    $this->get(route('aura.resource.view', [$post->type, $post->id]))
        ->assertSeeLivewire('aura::post-view')
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

    // LiveWire Component
    $component = livewire('aura::post-view', [$post->type, $post->id]);

    // Expect $component->viewFields to be an array
    expect($component->viewFields)->toBeArray();
});

test('resource view - can be customized via viewView method', function () {
    // Define a custom resource for this test
    class CustomViewResource extends Resource
    {
        public static string $type = 'CustomViewResource';

        public function viewView()
        {
            return 'custom.resource.view';
        }

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
    }

    Aura::registerResources([
        CustomViewResource::class,
    ]);

    // Add this at the beginning of your test
DB::listen(function($query) {
    ray($query->sql, $query->bindings)->orange();
});

// After your model creation
ray('Database connection:')->red();
ray(DB::connection()->getName())->red();

// ray('Tables in database:')->red();
// ray(DB::connection()->getDoctrineSchemaManager()->listTableNames())->red();

     // Create the custom resource
    $customResource = new CustomViewResource([
        'title' => 'Custom Resource Title',
        'content' => 'Custom Resource Content',
        'team_id' => 1,
        'type' => 'CustomViewResource',
    ]);

    ray('Before save:')->purple();
    ray($customResource)->purple();

    $saved = $customResource->save();

    ray('Save result:')->purple();
    ray($saved)->purple();

    ray('After save:')->purple();
    ray($customResource)->purple();

    $this->assertDatabaseHas('posts', ['type' => 'CustomViewResource']);

    // Check database state immediately after creation
    ray('Database state after creation:')->purple();
    ray(CustomViewResource::all())->purple();
    ray('Count: ' . CustomViewResource::count())->purple();

    dd(CustomViewResource::count());

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
    if (!file_exists(dirname($customViewPath))) {
        mkdir(dirname($customViewPath), 0777, true);
    }

    file_put_contents($customViewPath, $customViewContent);

    ray()->clearScreen();

     // Check database state immediately after creation
    ray('After creation:')->purple();
    ray(CustomViewResource::all())->purple();
    ray('Count: ' . CustomViewResource::count())->purple();

    // ... Your custom view file creation ...

    Aura::fake();
    Aura::setModel($query = CustomViewResource::query());

    // Check database state after Aura::fake()
    ray('After Aura::fake():')->red();
    ray(CustomViewResource::all())->red();
    ray('Count: ' . CustomViewResource::count())->red();

    ray($customResource);

    ray(CustomViewResource::get())->purple();

    dd(CustomViewResource::count());
    // Test the Livewire component
    
    $component = Livewire::test(View::class, ['slug' => 'CustomViewResource', 'id' => $customResource->id]);

    ray($component->html());

    $component->assertSee("Custom Resource View:");



    // Clean up: remove the temporary view file
    unlink($customViewPath);
    rmdir(dirname($customViewPath));
    rmdir(dirname(dirname($customViewPath)));
});