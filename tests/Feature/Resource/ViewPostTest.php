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
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // LiveWire Component
    $component = livewire('aura::post-view', [$post->type, $post->id]);

    // fake view exists in resources: aura/post/view.blade.php
})->todo();
