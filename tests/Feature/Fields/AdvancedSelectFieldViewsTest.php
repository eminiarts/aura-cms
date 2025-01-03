<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Post::factory(3)->create();
});

class HasManyFieldViewsModel extends Resource
{
    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Posts',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'resource' => Post::class,
                'slug' => 'posts',
            ],
        ];
    }
}


test('views can be customized', function () {
    
});
