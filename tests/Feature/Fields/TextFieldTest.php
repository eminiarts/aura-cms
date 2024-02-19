<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Text;
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

test('check Text Fields', function () {
    $slug = new Text();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'autocomplete'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'prefix'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'suffix'))->not->toBeNull();

});

test('Text Field - Name rendered', function () {

    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
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

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
    );

    expect((string) $view)->toContain('placeholder="Placeholder for Test"');
});

test('Text Field - Default Value set', function () {

    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'default' => 'Default for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
    );

    expect((string) $view)->toContain('value="Default for Test"');
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

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
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

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
    );

    expect((string) $view)->toContain('Suffix for Test');
});
