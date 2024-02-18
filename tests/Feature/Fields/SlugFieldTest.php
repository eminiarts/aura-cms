<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Aura\Base\Resource;
use Aura\Base\Fields\Slug;
use Aura\Base\Models\User;
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Aura\Base\Livewire\Resource\Edit;
use Illuminate\Support\Facades\Blade;
use Aura\Base\Livewire\Resource\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class SlugFieldModel extends Resource
{
    public static $singularName = 'Slug Model';

    public static ?string $slug = 'slug-model';

    public static string $type = 'SlugModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'text',
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [],
                'slug' => 'slug',
                'based_on' => 'text',
            ],
        ];
    }
}

test('Slug Field Test', function () {
    $model = new SlugFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Slug Model')
        ->assertSee('Slug for Test')
        ->assertSeeHtml('type="text"')
        ->assertSee('Slug')
        ->call('save')
        ->assertHasErrors(['form.fields.slug']);

    // Test custom slug
    $component
        ->set('form.fields.text', 'Custom Title')
        ->set('form.fields.slug', 'custom-title')
        ->call('save')
        ->assertHasNoErrors(['form.fields.slug']);

    // Assert that the model was saved to the database with the custom slug
    $this->assertDatabaseHas('posts', ['type' => 'SlugModel', 'slug' => 'custom-title']);

    // Get the saved model
    $post = SlugFieldModel::first();

    // Assert that $post->fields['slug'] is 'custom-slug'
    $this->assertEquals('custom-title', $post->fields['slug']);

    Aura::fake();
    Aura::setModel($model);

    $this->assertInstanceOf(SlugFieldModel::class, Aura::findResourceBySlug('SlugModel')->find($post->id));

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => 'SlugModel', 'id' => $post->id])
        ->set('form.fields.slug', 'toggle-slug')
        ->call('save')
        ->assertHasNoErrors(['form.fields.slug']);

    // Get the saved model
    $post = $post->refresh();

    // Assert that $model->fields['slug'] is 'toggle-slug'
    $this->assertEquals('toggle-slug', $post->slug);

    // Test validation
    $component->set('form.fields.slug', 'invalid slug')
        ->call('save')
        ->assertHasErrors(['form.fields.slug']);

    // Assert that the model was not saved to the database
    $this->assertDatabaseMissing('posts', ['type' => 'SlugModel', 'fields' => json_encode(['slug' => 'invalid slug'])]);
});

test('check Slug Fields', function () {
    $slug = new Slug();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'custom'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'disabled'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'based_on'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'based_on')['validation'])->toBe('required');
});

test('Slug Field - Without Custom Checkbox', function () {

    $model = new SlugFieldModel();

    $field =   [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'custom' => false,
    ];

    $fieldClass = app($field['type']);

    dump($fieldClass->component);

    // $blade = Blade::render('<x-dynamic-component :component="$field->component" :field="$data" />');


    $blade = Blade::render('<x-dynamic-component :component="$component" :field="$field" />', [
        'component' => $fieldClass->component,
        'field' => $field,
        'errors' => new \Illuminate\Support\MessageBag()
    ]);


    dd($blade);

    $view = view($fieldClass->component, ['field' => $field])->render();

    dd($view);
    $this->assertStringNotContainsString('.custom', $view);



    dd($field);

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Slug Model')
        ->assertSee('Slug for Test')
        ->assertSeeHtml('type="text"')
        ->assertSee('Slug')
        ->call('save')
        ->assertHasErrors(['form.fields.slug']);


});

test('Slug Field - only disabled input - true', function () {
});

test('Slug Field - disabled input - false', function () {
});

test('Slug Field - custom - false ', function () {
});

test('Slug Field - custom - true', function () {
});
