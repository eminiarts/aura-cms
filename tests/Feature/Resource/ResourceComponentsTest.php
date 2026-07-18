<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Tests\Resources\Post;

class ComponentsTestCustomView extends View {}

class ComponentsTestPost extends Post
{
    public static function viewComponent(): string
    {
        return ComponentsTestCustomView::class;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('resources default to the package page components', function () {
    expect(Post::indexComponent())->toBe(Index::class)
        ->and(Post::createComponent())->toBe(Create::class)
        ->and(Post::editComponent())->toBe(Edit::class)
        ->and(Post::viewComponent())->toBe(View::class);
});

test('an overridden component is bound to the default route and name', function () {
    Aura::fake();
    Aura::setModel(new ComponentsTestPost);

    $route = app('router')->getRoutes()->getByName('aura.post.view');

    expect($route->getActionName())->toContain(ComponentsTestCustomView::class);

    // The other routes keep the package components
    expect(app('router')->getRoutes()->getByName('aura.post.index')->getActionName())
        ->toContain(Index::class);
});

test('a custom view component serves the resource page', function () {
    Aura::fake();
    Aura::setModel(new ComponentsTestPost);

    $post = ComponentsTestPost::create([
        'title' => 'Custom Component Post',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    $this->get(route('aura.post.view', [$post->id]))
        ->assertSeeLivewire(ComponentsTestCustomView::class)
        ->assertSee('Custom Component Post');
});
