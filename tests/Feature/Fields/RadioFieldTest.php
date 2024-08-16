<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Fields\Radio;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Refresh Database on every test
uses(RefreshDatabase::class);

class RadioFieldModel extends Resource
{
    public static string $type = 'RadioModel';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Radio',
                'name' => 'Radio for Test',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'radio',
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

test('check Radio Fields', function () {
    $slug = new Radio;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'radio'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'options'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'key'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'value'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'default'))->not->toBeNull();
});

test('Radio Field - Name rendered', function () {
    $field = [
        'type' => 'Aura\\Base\\Fields\\Radio',
        'name' => 'Radio for Test',
        'default' => 'Default for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'radio',
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
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Radio for Test</label>');
});

test('Radio Field - Options rendered', function () {
    $field = [
        'type' => 'Aura\\Base\\Fields\\Radio',
        'name' => 'Radio for Test',
        'default' => 'Default for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'radio',
        'options' => [
            [
                'key' => 'option_1',
                'value' => 'Option 1',
            ],
            [
                'key' => 'option_2',
                'value' => 'Option 2',
            ],
        ],
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Radio for Test</label>');
    expect((string) $view)->toContain('Option 1');
    expect((string) $view)->toContain('Option 2');

    expect((string) $view)->toContain('value="option_1"');
    expect((string) $view)->toContain('value="option_2"');

    expect((string) $view)->toContain('type="radio"');
});
