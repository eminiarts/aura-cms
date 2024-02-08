<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Livewire\Resource\Create;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class BooleanFieldModel extends Resource
{
    public static $singularName = 'Boolean Model';

    public static ?string $slug = 'boolean-model';

    public static string $type = 'BooleanModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Boolean for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => 'boolean',
                'conditional_logic' => [],
                'slug' => 'boolean',
            ],
        ];
    }
}

test('Boolean Field Test', function () {
    // show all exceptions
    //$this->withoutExceptionHandling();

    $model = new BooleanFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Boolean Model')
        ->assertSee('Boolean for Test')
        ->assertSeeHtml('<button x-ref="toggle"')
        //->assertMissingHtml('class="bg-primary-600"')
        ->assertSeeHtml('bg-gray-300')
        ->assertSeeHtml('bg-primary-600')
        ->call('save')
        ->assertHasNoErrors(['resource.fields.boolean']);

    //->assertSeeHtml('type="email"')
    //->call('save')
    //->assertHasNoErrors(['resource.fields.email']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'BooleanModel']);

    // Get the saved model
    $model = BooleanFieldModel::first();

    // Assert that $model->fields['boolean'] is false
    $this->assertNull($model->fields['boolean']);
});
