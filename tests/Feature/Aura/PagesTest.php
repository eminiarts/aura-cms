<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = createSuperAdmin());
});

// Define datasets
dataset('auraPages', [
    'aura.settings',
    'aura.profile',
    // 'aura.config', // Not available atm
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
test('Check Index Pages', function ($postType) {
    $this->get(route('aura.resource.index', ['slug' => $postType]))->assertOk();
})->with('postTypes');

// Test Post Create Pages
test('Check Create Pages', function ($postType) {
    $this->withoutExceptionHandling();

    $this->get(route('aura.resource.create', ['slug' => $postType]))->assertOk();
})->with('postTypes');

// Test Post Edit and View Pages
test('Check Post Edit and View Pages', function ($postType) {
    $this->withoutExceptionHandling();
    // $post = createPost($postType); // Assumes a helper method to create a post

    $post = Aura::findResourceBySlug($postType)->factory()->create();

    $this->get(route('aura.resource.edit', ['slug' => $postType, 'id' => $post->id]))->assertOk();
    $this->get(route('aura.resource.view', ['slug' => $postType, 'id' => $post->id]))->assertOk();
})->with('postTypes');
