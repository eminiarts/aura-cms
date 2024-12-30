<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use DB;
use Livewire\Livewire;
use Mockery;

use function Pest\Laravel\postJson;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new AdvancedSelectFieldMetaModel);
});

// Create Resource for this test
class AdvancedSelectFieldMetaModel extends Resource
{
    public static $singularName = 'AdvancedSelect Model';

    public static ?string $slug = 'advancedselect';

    public static string $type = 'AdvancedSelectMetaModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'AdvancedSelect for Test',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'advancedselect',
                'resource' => 'Aura\\Base\\Resources\\User',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'polymorphic_relation' => false,
                'multiple' => true,
            ],
        ];
    }
}

test('AdvancedSelect Meta Field Test', function () {
    $model = new AdvancedSelectFieldMetaModel;

    $component = Livewire::test(Create::class, ['slug' => 'advancedselect'])
        ->call('setModel', $model)
        ->assertSee('Create AdvancedSelect Model')
        ->assertSee('AdvancedSelect for Test')
        ->assertSeeHtml('x-text="selectedItemMarkup(item).title"')
        ->call('save')
        ->assertHasNoErrors(['form.fields.advancedselect']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'AdvancedSelectMetaModel']);

    $model = AdvancedSelectFieldMetaModel::first();

    $users = User::get();

    // Assert that $model->fields['number'] is null
    $this->assertEmpty($model->fields['advancedselect']);

    $component->set('form.fields.advancedselect', [$users[0]->id])
        ->call('save')
        ->assertHasNoErrors(['form.fields.advancedselect']);

    // get the datemodel from db
    $model = AdvancedSelectFieldMetaModel::orderBy('id', 'desc')->first();

    // Dump and die the post_relation table
    // dd(DB::table('post_relations')->get());

    expect($model->fields['advancedselect'])->toBeArray();
    expect($model->advancedselect)->toHaveCount(1);
    expect($model->fields['advancedselect'])->toContain($users[0]->id);

});

test('advancedselect field gets displayed correctly on edit view', function () {
    $model = AdvancedSelectFieldMetaModel::create([
        'fields' => [
            'advancedselect' => [$id = User::first()->id],
        ],
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'AdvancedSelectMetaModel']);

    $post = AdvancedSelectFieldMetaModel::first();

    // expect($post->advancedselect)->toBeArray();
    // dd($post->advancedselect->pluck('id'));
    expect($post->advancedselect)->toHaveCount(1);
    expect($post->fields['advancedselect'])->toBeArray();
    expect($post->advancedselect->pluck('id'))->toContain($id);

    $model = new AdvancedSelectFieldMetaModel;
    $slug = 'AdvancedSelectMetaModel';

    Aura::fake();
    Aura::setModel($model);

    $user = User::first();

    // If we call the edit view, the advancedselect field should be empty
    $component = Livewire::test(Edit::class, ['slug' => $slug, 'id' => $post->id])
        ->assertSee('Edit AdvancedSelect Model')
        ->assertSee('AdvancedSelect for Test')
        ->assertSeeHtml('<span x-show="isSelected(item)" class="font-semibold text-primary-600">&check;</span>')
        ->call('save');

    $post = AdvancedSelectFieldMetaModel::first();

    expect($post->fields['advancedselect'])->toBeArray();
    expect($post->fields['advancedselect'])->toHaveCount(1);
    expect($post->fields['advancedselect'])->toContain($id);

});

test('Advanced Select - Fields', function () {
    $slug = new AdvancedSelect;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'create'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'multiple'))->not->toBeNull();
});

test('Advanced Select - Check values function exists', function () {
    $advancedSelect = new AdvancedSelect;
    expect(method_exists($advancedSelect, 'values'))->toBeTrue();
});

// Test for Missing `model` or `slug` Parameters
it('returns an error if model or slug is missing', function () {
    $response = postJson(route('aura.api.fields.values'), [
        // Intentionally leaving out 'model' and 'slug'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Missing model or slug',
        ]);
});

it('returns field values for a valid request', function () {

    $response = test()->postJson(route('aura.api.fields.values'), [
        'model' => User::class, // Replace with actual model class string
        'slug' => 'text',
        'field' => AdvancedSelect::class, // This must match your actual field class name or identifier
    ]);

    $user = User::first();

    $response->assertStatus(200)
        ->assertJson([
            ['id' => $user->id, 'title' => $user->title()],
        ]);
});

test('Advanced Select Field - entangle', function () {
    $field = [
        'name' => 'Select',
        'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
        'create' => true,
        'validation' => '',
        'resource' => 'Aura\\Base\\Resources\\User',
        'slug' => 'select',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('value: $wire.entangle(\'form.fields.select\'),');
});

test('Advanced Select Field - create button true', function () {
    $field = [
        'name' => 'Select',
        'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
        'create' => true,
        'validation' => '',
        'resource' => 'Aura\\Base\\Resources\\User',
        'slug' => 'select',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Select');
    expect((string) $view)->toContain('wire:click="$dispatch(\'openModal\', { component:');
});

test('Advanced Select Field - create button false', function () {
    $field = [
        'name' => 'Select',
        'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
        'create' => false,
        'validation' => '',
        'resource' => 'Aura\\Base\\Resources\\User',
        'slug' => 'select',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Select');
    expect((string) $view)->not->toContain('wire:click="$dispatch(\'openModal\', { component:');
});

test('searchable fields API Mock', function () {

    $user = Mockery::mock(User::class);

    $response = postJson(route('aura.api.fields.values'), [
        'model' => User::class, // Replace with actual model class string
        'slug' => 'text',
        'field' => AdvancedSelect::class, // This must match your actual field class name or identifier
    ]);

    $response->assertStatus(200);

    $field = new AdvancedSelect;

    $request = new \Illuminate\Http\Request([
        'model' => User::class,
        'slug' => 'select',
        'search' => '',
        'field' => AdvancedSelect::class,
    ]);

    $result = $field->api($request);

    expect($result)->toHavecount(1);

    $request = new \Illuminate\Http\Request([
        'model' => User::class,
        'slug' => 'select',
        'search' => 'notexists',
        'field' => AdvancedSelect::class,
    ]);

    $result = $field->api($request);

    expect($result)->toHavecount(0);

    // Does not work somehow, always passes
    // $user->shouldReceive('getSearchableFields')->andReturn(['title2']);

    // // Mock the searchIn method to return a collection of items
    // $user->shouldReceive('searchIn')->andReturnSelf();

    // $user->shouldReceive('take')->with(20)->andReturnSelf();

    // $user->shouldReceive('get')->andReturn(collect([
    //     (object)['id' => 1, 'title' => 'Role 1'],
    //     (object)['id' => 2, 'title' => 'Role 2'],
    // ]));

});
