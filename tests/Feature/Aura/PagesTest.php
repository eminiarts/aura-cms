<?php

use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Resources\Permission;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Tag;
use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = createSuperAdmin());
});

// Define datasets
dataset('auraPages', [
    'aura.team.settings',
    'aura.profile',
    'aura.config',
]);

dataset('postTypes', [
    'Option',
    'User',
    'Post',
    'Role',
    'Permission',
    'Attachment',
]);

// Test Aura Pages
test('Check Aura Pages (with Teams)', function ($routeName) {
    expect(config('aura.teams'))->toBeTrue();
    $this->get(route($routeName))->assertOk();
})->with('auraPages');

// Test Post Index Pages
test('Check Post Index Pages', function ($postType) {
    $this->get(route('aura.post.index', ['slug' => $postType]))->assertOk();
})->with('postTypes');

// Test Post Create Pages
test('Check Post Create Pages', function ($postType) {
    $this->get(route('aura.post.create', ['slug' => $postType]))->assertOk();
})->with('postTypes');

// Test Post Edit and View Pages
test('Check Post Edit and View Pages', function ($postType) {
    $post = createPost($postType); // Assumes a helper method to create a post

    $this->get(route('aura.post.edit', ['slug' => $postType, 'id' => $post->id]))->assertOk();
    $this->get(route('aura.post.view', ['slug' => $postType, 'id' => $post->id]))->assertOk();
})->with('postTypes');