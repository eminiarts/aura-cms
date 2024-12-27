<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Resources\User;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Re
class HasManyCustomTableModel extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => Post::class,
                'slug' => 'posts',
                'table_settings' => [
                    'per_page' => 5,
                ],
            ],
        ];
    }

    // public function posts()
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }
}

test('Only 5 posts shown in View', function () {
    $model = new HasManyCustomTableModel;

    // Create 10 posts
    Post::factory()->count(10)->create();

    // Visit the Post Index Page
    $this->get(route('aura.has-many-model.index'))
        ->assertSeeLivewire('aura::resource-index')
        ->assertSeeLivewire('aura::table')
        ->assertSee('5');
});

test('search in hasMany does not lose query scope', function () {
    $model = new HasManyCustomTableModel;

    // Create 10 posts
    Post::factory()->count(10)->create();

    // Visit the Post Index Page

});
