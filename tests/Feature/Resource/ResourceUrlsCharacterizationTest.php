<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Characterization tests for the route-builder helpers in AuraModelConfig:
 * createUrl / editUrl / viewUrl / indexUrl / getIndexRoute. Pins the EXACT
 * current behaviour (including which methods guard on $id and which throw when
 * a route is absent) before the ResourceUrls extraction (design §1/§4 step 2).
 */

// A resource whose slug is never registered, so none of its aura.* routes exist.
class NoRouteResource extends Resource
{
    public static ?string $slug = 'characterization-no-route';

    public static string $type = 'NoRouteResource';
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Aura::setModel() registers aura.post.{index,create,edit,view} for this slug.
    Aura::fake();
    Aura::setModel(new Post);
});

test('createUrl returns the route string when the create route exists', function () {
    $post = Post::create(['type' => 'Post']);

    expect($post->createUrl())->toBe(route('aura.post.create'));
});

test('createUrl ignores id: an unsaved model still returns the create route', function () {
    $post = new Post;

    // createUrl() has no id/type guard, so a brand-new model returns the route.
    expect($post->createUrl())->toBe(route('aura.post.create'));
});

test('createUrl returns null when the create route is absent', function () {
    $model = new NoRouteResource;

    expect($model->createUrl())->toBeNull();
});

test('indexUrl returns the route string when the index route exists', function () {
    $post = new Post;

    expect($post->indexUrl())->toBe(route('aura.post.index'));
});

test('indexUrl returns null when the index route is absent', function () {
    $model = new NoRouteResource;

    expect($model->indexUrl())->toBeNull();
});

test('editUrl returns the route string for a saved model when the edit route exists', function () {
    $post = Post::create(['type' => 'Post']);

    expect($post->editUrl())->toBe(route('aura.post.edit', ['id' => $post->id]));
});

test('editUrl returns null for an unsaved model even when the edit route exists', function () {
    $post = new Post;

    // editUrl() guards on `! $this->id` and returns early, before Route::has().
    expect($post->editUrl())->toBeNull();
});

test('editUrl returns null when the edit route is absent', function () {
    $model = NoRouteResource::create(['type' => 'NoRouteResource']);

    expect($model->editUrl())->toBeNull();
});

test('viewUrl returns the route string for a saved model when the view route exists', function () {
    $post = Post::create(['type' => 'Post']);

    expect($post->viewUrl())->toBe(route('aura.post.view', ['id' => $post->id]));
});

test('viewUrl returns null for an unsaved model even when the view route exists', function () {
    $post = new Post;

    // viewUrl() guards on `! $this->id` and returns early, before Route::has().
    expect($post->viewUrl())->toBeNull();
});

test('viewUrl returns null when the view route is absent', function () {
    $model = NoRouteResource::create(['type' => 'NoRouteResource']);

    expect($model->viewUrl())->toBeNull();
});

test('getIndexRoute returns the route string when the index route exists', function () {
    $post = new Post;

    expect($post->getIndexRoute())->toBe(route('aura.post.index'));
});

test('getIndexRoute throws when the index route is absent (no guard, unlike the *Url helpers)', function () {
    $model = new NoRouteResource;

    // Surprising: getIndexRoute() calls route() unconditionally with no Route::has()
    // guard, so an unregistered resource throws rather than returning null.
    $model->getIndexRoute();
})->throws(RouteNotFoundException::class);
