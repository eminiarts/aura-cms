<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Radio;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class RadioFieldModel extends Resource
{
    public static $singularName = 'Radio Model';

    public static ?string $slug = 'radio-model';

    public static string $type = 'RadioModel';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Radio',
                'name' => 'Radio for Test',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'radio',
                'options' => [
                    ['key' => 'option_1', 'value' => 'Option 1'],
                    ['key' => 'option_2', 'value' => 'Option 2'],
                    ['key' => 'option_3', 'value' => 'Option 3'],
                ],
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Radio Field Configuration', function () {
    test('has required configuration fields', function () {
        $radioField = new Radio;
        $fields = collect($radioField->getFields());

        expect($fields->firstWhere('slug', 'radio'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'options'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'key'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'value'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $radioField = new Radio;

        expect($radioField->optionGroup)->toBe('Choice Fields');
    });

    test('has correct edit property', function () {
        $radioField = new Radio;

        expect($radioField->edit)->toBe('aura::fields.radio');
    });

    test('edit method returns edit property', function () {
        $radioField = new Radio;

        expect($radioField->edit())->toBe('aura::fields.radio');
    });

    test('options field uses Repeater type', function () {
        $radioField = new Radio;
        $fields = collect($radioField->getFields());

        $optionsField = $fields->firstWhere('slug', 'options');
        expect($optionsField['type'])->toBe('Aura\\Base\\Fields\\Repeater');
    });

    test('default field has exclude_from_nesting flag', function () {
        $radioField = new Radio;
        $fields = collect($radioField->getFields());

        $defaultField = $fields->firstWhere('slug', 'default');
        expect($defaultField['exclude_from_nesting'])->toBeTrue();
    });
});

describe('Radio Field Rendering', function () {
    test('renders field name as label', function () {
        $field = [
            'type' => 'Aura\\Base\\Fields\\Radio',
            'name' => 'Radio for Test',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'radio',
            'options' => [
                ['key' => 'option_1', 'value' => 'Option 1'],
                ['key' => 'option_2', 'value' => 'Option 2'],
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

    test('renders all options with correct values', function () {
        $field = [
            'type' => 'Aura\\Base\\Fields\\Radio',
            'name' => 'Radio for Test',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'radio',
            'options' => [
                ['key' => 'option_1', 'value' => 'Option 1'],
                ['key' => 'option_2', 'value' => 'Option 2'],
            ],
        ];

        $fieldClass = app($field['type']);
        $field['field'] = $fieldClass;

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('Option 1')
            ->and((string) $view)->toContain('Option 2')
            ->and((string) $view)->toContain('value="option_1"')
            ->and((string) $view)->toContain('value="option_2"')
            ->and((string) $view)->toContain('type="radio"');
    });
});

describe('Radio Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new RadioFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'radio-model'])
            ->assertSee('Radio for Test')
            ->assertSee('Option 1')
            ->assertSee('Option 2')
            ->assertSee('Option 3');
    });

    test('saves selected option', function () {
        Livewire::test(Create::class, ['slug' => 'radio-model'])
            ->set('form.fields.radio', 'option_2')
            ->call('save')
            ->assertHasNoErrors();

        $model = RadioFieldModel::first();
        expect($model->radio)->toBe('option_2');
    });

    test('saves null when no option selected', function () {
        Livewire::test(Create::class, ['slug' => 'radio-model'])
            ->call('save')
            ->assertHasNoErrors();

        $model = RadioFieldModel::first();
        expect($model->fields['radio'])->toBeNull();
    });
});

describe('Radio Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $radioField = new Radio;

        expect($radioField->get(null, 'option_1'))->toBe('option_1')
            ->and($radioField->get(null, null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $radioField = new Radio;

        expect($radioField->value('option_1'))->toBe('option_1')
            ->and($radioField->value(''))->toBe('');
    });

    test('filterOptions returns correct filters', function () {
        $radioField = new Radio;
        $options = $radioField->filterOptions();

        expect($options)->toHaveKey('is')
            ->and($options)->toHaveKey('is_not')
            ->and($options)->toHaveKey('is_empty')
            ->and($options)->toHaveKey('is_not_empty');
    });
});
