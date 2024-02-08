<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Models\User;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class NumberFieldModel extends Resource
{
    public static $singularName = 'Number Model';

    public static ?string $slug = 'number-model';

    public static string $type = 'NumberModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Number for Test',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric|nullable',
                'conditional_logic' => [],
                'suffix' => '%',
                'prefix' => 'CHF',
                'slug' => 'number',
            ],
        ];
    }
}

test('Number Field can be rendered', function () {
    // show all exceptions
    //$this->withoutExceptionHandling();

    $model = new NumberFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Number Model')
        ->assertSee('Number for Test')
        ->assertSeeHtml('type="number"')
        ->assertSee('CHF')
        ->assertSee('%')
        ->call('save')
        ->assertHasNoErrors(['form.fields.number']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'NumberModel']);

    $model = NumberFieldModel::first();

    // Assert that $model->fields['number'] is null
    $this->assertNull($model->fields['number']);

    $component->set('form.fields.number', '2021-01-01')
        ->call('save')
        ->assertHasErrors(['form.fields.number'])
        ->set('form.fields.number', '5')
        ->call('save')
        ->assertHasNoErrors(['form.fields.number']);

    // get the datemodel from db
    $model = NumberFieldModel::orderBy('id', 'desc')->first();

    expect($model->fields['number'])->toBe('5');
    expect($model->number)->toBe('5');
    // Prepend CHF to the number and assert it is visible

    // Append "%" to the number and assert it is visible
});
