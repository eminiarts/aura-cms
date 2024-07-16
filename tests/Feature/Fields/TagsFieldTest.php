<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Aura\Base\Resource;
use Aura\Base\Fields\Tags;
use Aura\Base\Fields\Text;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class TagsFieldModel extends Resource
{
    public static string $type = 'TagsModel';

    public static function getFields()
    {
        return [
             [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

test('check Tags Fields', function () {
    $slug = new Tags();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'create'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
});

test('Tags Field - Name rendered', function () {
    $field = [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Text for Test</label>');
});

test('Text Field - Placeholder rendered', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'placeholder' => 'Placeholder for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('placeholder="Placeholder for Test"');
});

test('Text Field - Default Value set', function () {
    Aura::fake();
    Aura::setModel(new TagsFieldModel());

    $component = Livewire::test(Create::class, ['slug' => 'TagsModel'])
        ->assertSee('Text for Test')
        ->assertSeeHtml('wire:model="form.fields.text"')
        ->assertDontSee('Advanced Text')
        ->assertSet('form.fields.text', 'Default for Test');

});

test('Text Field - Prefix rendered', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'prefix' => 'Prefix for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Prefix for Test');
});

test('Text Field - suffix rendered', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'suffix' => 'Suffix for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Suffix for Test');
});
