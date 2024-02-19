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

test('check Text Fields', function () {
    $slug = new Slug();

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
        ['component' => $fieldClass->component, 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->not->toContain('<div class="custom-slug');
    expect((string) $view)->toContain('custom: true,');
    expect((string) $view)->toContain('value: $wire.entangle(\'form.fields.slug\')');

    // Set Custom

    $field['custom'] = true;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" />',
        ['component' => $fieldClass->component, 'field' => $field]
    );

    $view->assertSee('Slug for Test');

    expect((string) $view)->toContain('<div class="custom-slug');

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
        ['component' => $fieldClass->component, 'field' => $field]
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
        ['component' => $fieldClass->component, 'field' => $field]
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
        ['component' => $fieldClass->component, 'field' => $field]
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
        ['component' => $fieldClass->component, 'field' => $field]
    );

    expect((string) $view)->toContain('<div class="custom-slug');
});
