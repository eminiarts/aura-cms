<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

class CheckboxFieldModel extends Resource
{
    public static string $type = 'CheckboxModel';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Checkbox',
                'name' => 'Checkbox for Test',
                'default' => 'Default for Test',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'checkbox',
                'options' => [
                    [
                        'key' => 'Option 1',
                        'value' => 'Option 1',
                    ],
                    [
                        'key' => 'Option 2',
                        'value' => 'Option 2',
                    ],
                ],
            ],

        ];
    }
}

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('check Checkbox Fields', function () {
    $slug = new Checkbox();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'checkbox'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'options'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'key'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'value'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'default'))->not->toBeNull();
});

test('Checkbox Field - Name rendered', function () {
    $field = [
        'type' => 'Aura\\Base\\Fields\\Checkbox',
        'name' => 'Checkbox for Test',
        'default' => 'Default for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'checkbox',
        'options' => [
            [
                'key' => 'Option 1',
                'value' => 'Option 1',
            ],
            [
                'key' => 'Option 2',
                'value' => 'Option 2',
            ],
        ],
    ];


    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Checkbox for Test</label>');
});


test('Checkbox Field - Options rendered', function () {
    $field = [
        'type' => 'Aura\\Base\\Fields\\Checkbox',
        'name' => 'Checkbox for Test',
        'default' => 'Default for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'checkbox',
        'options' => [
            [
                'key' => 'Option 1',
                'value' => 'Option 1',
            ],
            [
                'key' => 'Option 2',
                'value' => 'Option 2',
            ],
        ],
    ];


    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Checkbox for Test</label>');
    expect((string) $view)->toContain('Option 1');
    expect((string) $view)->toContain('Option 2');
});
