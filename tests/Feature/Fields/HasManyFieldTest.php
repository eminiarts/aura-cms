<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class HasManyFieldModel extends Resource
{
    public static $singularName = 'HasMany Model';

    public static ?string $slug = 'hasmany-model';

    public static string $type = 'HasManyModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Hasmany for Test',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\Post',
                'validation' => 'numeric|nullable',
                'conditional_logic' => [],
                'suffix' => '%',
                'prefix' => 'CHF',
                'slug' => 'hasmany',
            ],
        ];
    }
}

test('HasMany Field not shown in Create', function () {
    $model = new HasManyFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model);
    //->assertSee('Hasmany for Test')
});

test('HasMany Field shown on Edit', function () {
    $model = new HasManyFieldModel();

});

test('HasMany query Meta Fields with posts table', function () {
});

test('HasMany query with custom tables', function () {
});
