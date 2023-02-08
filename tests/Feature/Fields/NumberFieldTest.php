<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

// Create Resource for this test
class NumberFieldModel extends Post
{
    public static $singularName = 'Number Model';

    public static ?string $slug = 'number-model';

    public static string $type = 'NumberModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Number for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
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
        ->assertHasNoErrors(['post.fields.number']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'NumberModel']);

    $model = NumberFieldModel::first();

    // Assert that $model->fields['number'] is null
    $this->assertNull($model->fields['number']);

    $component->set('post.fields.number', '2021-01-01')
        ->call('save')
        ->assertHasErrors(['post.fields.number'])
        ->set('post.fields.number', '5')
        ->call('save')
        ->assertHasNoErrors(['post.fields.number']);

    // get the datemodel from db
    $model = NumberFieldModel::orderBy('id', 'desc')->first();

    expect($model->fields['number'])->toBe('5');
    expect($model->number)->toBe('5');
    // Prepend CHF to the number and assert it is visible

    // Append "%" to the number and assert it is visible
});
