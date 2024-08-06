<?php

use Aura\Base\Resources\Post;

use function Pest\Livewire\livewire;

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

test('post view - can be customized', function () {
    $post = Post::factory()->create();
    
    // Assuming you have a custom view at resources/views/vendor/aura/post/view.blade.php
    $customViewPath = resource_path('views/vendor/aura/post/view.blade.php');
    
    // Create a temporary custom view file
    file_put_contents($customViewPath, '<div>Custom Post View: {{ $post->title }}</div>');
    
    $response = $this->get(route('aura.resource.view', ['slug' => 'Post', 'id' => $post->id]));
    
    $response->assertSee("Custom Post View: {$post->title}");
    
    // Clean up: remove the temporary view file
    unlink($customViewPath);
});