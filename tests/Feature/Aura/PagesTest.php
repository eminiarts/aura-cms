<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Before each test, create a Superadmin and login
beforeEach(function () {
    Artisan::call('cache:clear');
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
    $this->get(route($routeName))->assertOk();
})->with('auraPages');

// Test Post Index Pages
test('Check Index Pages', function ($postType) {
    $this->get(route("aura.{$postType}.index"))->assertOk();
})->with('postTypes');

dataset('crudPostTypes', [
    'option',
    'user',
    'role',
    'permission',
    // attachment deliberately has only aura.attachment.index (media library)
]);

// Test Post Create Pages
test('Check Create Pages', function ($postType) {
    $this->withoutExceptionHandling();

    $this->get(route("aura.{$postType}.create"))->assertOk();
})->with('crudPostTypes');

// Test Post Edit and View Pages
test('Check Post Edit and View Pages', function ($postType) {
    if (! config('aura.teams') && in_array($postType, ['user', 'role'], true)) {
        $this->markTestSkipped('This resource fixture is team-scoped.');
    }

    // $post = createPost($postType); // Assumes a helper method to create a post

    $post = Aura::findResourceBySlug($postType)->factory()->create();

    // For user resources, ensure the created user is in the same team as current user
    if ($postType === 'user') {
        $currentUser = auth()->user();
        $currentUserRole = $currentUser->roles->first();
        $post->update(['current_team_id' => $currentUser->current_team_id]);

        // Associate the user with the team through the role
        $post->roles()->syncWithPivotValues([$currentUserRole->id], ['team_id' => $currentUser->current_team_id]);
    }

    $this->get(route("aura.{$postType}.edit", ['id' => $post->id]))->assertOk();
    $this->get(route("aura.{$postType}.view", ['id' => $post->id]))->assertOk();
})->with('crudPostTypes');

test('attachment has index route but no create edit or view routes', function () {
    expect(Route::has('aura.attachment.index'))->toBeTrue()
        ->and(Route::has('aura.attachment.create'))->toBeFalse()
        ->and(Route::has('aura.attachment.edit'))->toBeFalse()
        ->and(Route::has('aura.attachment.view'))->toBeFalse();
});
