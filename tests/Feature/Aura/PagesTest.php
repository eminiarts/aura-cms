<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    Artisan::call('cache:clear');

    $this->withoutExceptionHandling();
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
    'option',
    'user',
    'post',
    'role',
    'permission',
    'attachment',
]);

// Test Post Index Pages
test('Check user authorization', function () {
    expect(auth()->user()->isSuperAdmin())->toBeTrue();
});

// Test Aura Pages
test('Check Aura Pages (with Teams)', function ($routeName) {
    expect(config('aura.teams'))->toBeTrue();
    $this->get(route($routeName))->assertOk();
})->with('auraPages');

// Test Post Index Pages
test('Check Index Pages', function ($postType) {
    $this->get(route("aura.{$postType}.index"))->assertOk();
})->with('postTypes');

// Test Post Create Pages
test('Check Create Pages', function ($postType) {
    $this->withoutExceptionHandling();

    $this->get(route("aura.{$postType}.create"))->assertOk();
})->with('postTypes');

// Test Post Edit and View Pages
test('Check Post Edit and View Pages', function ($postType) {
    $this->withoutExceptionHandling();
    // $post = createPost($postType); // Assumes a helper method to create a post

    $post = Aura::findResourceBySlug($postType)->factory()->create();

    $this->get(route("aura.{$postType}.edit", ['id' => $post->id]))->assertOk();
    $this->get(route("aura.{$postType}.view", ['id' => $post->id]))->assertOk();
})->with('postTypes');
