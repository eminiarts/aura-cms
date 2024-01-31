<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Facades\Hash;
use Eminiarts\Aura\Livewire\Post\Edit;
use Eminiarts\Aura\Livewire\Post\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class AdvancedSelectFieldModel extends Resource
{
    public static $singularName = 'AdvancedSelect Model';

    public static ?string $slug = 'advancedselect-model';

    public static string $type = 'AdvancedSelectModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'AdvancedSelect for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\AdvancedSelect',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'advancedselect',
                'resource' => 'Eminiarts\\Aura\\Resources\\Role',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],
        ];
    }
}

test('AdvancedSelect Field Test', function () {
    $model = new AdvancedSelectFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create AdvancedSelect Model')
        ->assertSee('AdvancedSelect for Test')
        ->assertSeeHtml('x-text="selectedItem(item)"')
        ->call('save')
        ->assertHasNoErrors(['post.fields.advancedselect']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'AdvancedSelectModel']);

    $model = AdvancedSelectFieldModel::first();

    $roles = Role::get();

    // Assert that $model->fields['number'] is null
    $this->assertNull($model->fields['advancedselect']);

    $component->set('post.fields.advancedselect', [ $roles[0]->id ])
        ->call('save')
        ->assertHasNoErrors(['post.fields.advancedselect']);

    // get the datemodel from db
    $model = AdvancedSelectFieldModel::orderBy('id', 'desc')->first();

    expect($model->fields['advancedselect'])->toBeArray();
    expect($model->fields['advancedselect'])->toHaveCount(1);
    expect($model->fields['advancedselect'])->toContain($roles[0]->id);
    
    expect($model->advancedselect)->toBeArray();
    expect($model->advancedselect)->toHaveCount(1);
    expect($model->advancedselect)->toContain($roles[0]->id);
    
});

test('advancedselect field gets displayed correctly on edit view', function () {
    $model = AdvancedSelectFieldModel::create([
        'fields' => [
            'advancedselect' => [ $id =Role::first()->id ]
        ]
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'AdvancedSelectModel']);

    $post = AdvancedSelectFieldModel::first();

    expect($post->advancedselect)->toBeArray();
    expect($post->advancedselect)->toHaveCount(1);
    expect($post->advancedselect)->toContain($id);

    $model = AdvancedSelectFieldModel::query();
    $slug = 'AdvancedSelectModel';

    Aura::fake();
    Aura::setModel($model);

    $role = Role::first();

    // If we call the edit view, the advancedselect field should be empty
    $component = Livewire::test(Edit::class, ['slug' => $slug, 'id' => $post->id])
        ->assertSee('Edit AdvancedSelect Model')
        ->assertSee('AdvancedSelect for Test')
        ->assertSeeHtml('Super Admin (#1)')
        ->assertSeeHtml('<span x-show="isSelected(item)" class="font-semibold text-primary-600">&check;</span>')
        ->call('save');

    $post = AdvancedSelectFieldModel::first();

    expect($post->advancedselect)->toBeArray();
    expect($post->advancedselect)->toHaveCount(1);
    expect($post->advancedselect)->toContain($id);
});



test('api test with advanced select', function () {
});

