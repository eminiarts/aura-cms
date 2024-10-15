<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Slug;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
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
    $model = new SlugFieldModel;

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
    $slug = new Slug;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'custom'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'disabled'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'based_on'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'based_on')['validation'])->toBe('required');
});

test('Slug Field - Without Custom Checkbox', function () {

    $field = [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'custom' => false,
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->not->toContain('<div class="custom-slug');
    expect((string) $view)->toContain('custom: true,');
    expect((string) $view)->toContain('value: $wire.entangle(\'form.fields.slug\')');

    // Set Custom

    $field['custom'] = true;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->toContain('<div class="flex flex-col custom-slug');

    expect((string) $view)->toContain('custom: true,');
});

test('Slug Field - only disabled input - true', function () {
    $field = [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'custom' => false,
        'disabled' => true,
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->not->toContain('<div class="custom-slug');
    expect((string) $view)->toContain('custom: false,');
    expect((string) $view)->toContain('x-bind:disabled="!custom"');

});

test('Slug Field - disabled input - false', function () {
    $field = [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'disabled' => false,
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->not->toContain('<div class="custom-slug');
    expect((string) $view)->toContain('custom: true,');
    expect((string) $view)->toContain('x-bind:disabled="!custom"');

});

test('Slug Field - custom - false ', function () {

    $field = [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'custom' => false,
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    expect((string) $view)->not->toContain('<div class="custom-slug');
});

test('Slug Field - custom - true', function () {
    $field = [
        'name' => 'Slug for Test',
        'type' => 'Aura\\Base\\Fields\\Slug',
        'validation' => 'required|alpha_dash',
        'conditional_logic' => [],
        'slug' => 'slug',
        'based_on' => 'text',
        'custom' => true,
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->edit(), 'field' => $field]
    );

    expect((string) $view)->toContain('<div class="flex flex-col custom-slug');
});
