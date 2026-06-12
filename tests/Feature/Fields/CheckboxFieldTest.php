<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class CheckboxFieldModel extends Resource
{
    public static $singularName = 'Checkbox Model';

    public static ?string $slug = 'checkbox-model';

    public static string $type = 'CheckboxModel';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Checkbox',
                'name' => 'Checkbox for Test',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'checkbox',
                'options' => [
                    ['key' => 'option_1', 'value' => 'Option 1'],
                    ['key' => 'option_2', 'value' => 'Option 2'],
                    ['key' => 'option_3', 'value' => 'Option 3'],
                ],
            ],
        ];
    }
}

class CheckboxFieldModelWithDynamicOptions extends Resource
{
    public static $singularName = 'Checkbox Dynamic Model';

    public static ?string $slug = 'checkbox-dynamic';

    public static string $type = 'CheckboxDynamicModel';

    public function getColorsOptions(): array
    {
        return [
            ['key' => 'red', 'value' => 'Red'],
            ['key' => 'blue', 'value' => 'Blue'],
        ];
    }

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Checkbox',
                'name' => 'Dynamic Checkbox',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'colors',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Checkbox Field Configuration', function () {
    test('has required configuration fields', function () {
        $checkboxField = new Checkbox;
        $fields = collect($checkboxField->getFields());

        expect($fields->firstWhere('slug', 'checkbox'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'options'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'key'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'value'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->optionGroup)->toBe('Choice Fields');
    });

    test('has correct edit property', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->edit)->toBe('aura::fields.checkbox');
    });

    test('edit method returns edit property', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->edit())->toBe('aura::fields.checkbox');
    });
});

describe('Checkbox Field Rendering', function () {
    test('renders field name as label', function () {
        $field = [
            'type' => 'Aura\\Base\\Fields\\Checkbox',
            'name' => 'Checkbox for Test',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'checkbox',
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

        expect((string) $view)->toContain('>Checkbox for Test</label>');
    });

    test('renders all options with correct values', function () {
        $field = [
            'type' => 'Aura\\Base\\Fields\\Checkbox',
            'name' => 'Checkbox for Test',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'checkbox',
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
            ->and((string) $view)->toContain('type="checkbox"');
    });
});

describe('Checkbox Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new CheckboxFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'checkbox-model'])
            ->assertSee('Checkbox for Test')
            ->assertSee('Option 1')
            ->assertSee('Option 2')
            ->assertSee('Option 3');
    });

    test('saves empty array when no options selected', function () {
        Livewire::test(Create::class, ['slug' => 'checkbox-model'])
            ->call('save')
            ->assertHasNoErrors();

        $model = CheckboxFieldModel::first();
        expect($model->fields['checkbox'])->toBe([]);
    });

    test('saves selected options', function () {
        Livewire::test(Create::class, ['slug' => 'checkbox-model'])
            ->set('form.fields.checkbox', ['option_1', 'option_3'])
            ->call('save')
            ->assertHasNoErrors();

        $model = CheckboxFieldModel::first();
        expect($model->checkbox)->toBe(['option_1', 'option_3']);
    });
});

describe('Checkbox Field Value Handling', function () {
    test('get method returns empty array for null', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->get(null, null))->toBe([]);
    });

    test('get method returns empty array for false', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->get(null, false))->toBe([]);
    });

    test('get method returns array unchanged', function () {
        $checkboxField = new Checkbox;
        $value = ['option_1', 'option_2'];

        expect($checkboxField->get(null, $value))->toBe($value);
    });

    test('get method decodes JSON string', function () {
        $checkboxField = new Checkbox;
        $value = '["option_1","option_2"]';

        expect($checkboxField->get(null, $value))->toBe(['option_1', 'option_2']);
    });

    test('set method encodes array to JSON', function () {
        $checkboxField = new Checkbox;
        $value = ['option_1', 'option_2'];

        expect($checkboxField->set(null, [], $value))->toBe('["option_1","option_2"]');
    });

    test('set method returns non-array values unchanged', function () {
        $checkboxField = new Checkbox;

        expect($checkboxField->set(null, [], 'string_value'))->toBe('string_value');
    });
});

describe('Checkbox Field Options', function () {
    test('options method returns field options', function () {
        $checkboxField = new Checkbox;
        $model = new CheckboxFieldModel;
        $field = [
            'slug' => 'checkbox',
            'options' => [
                ['key' => 'opt1', 'value' => 'Option 1'],
                ['key' => 'opt2', 'value' => 'Option 2'],
            ],
        ];

        $options = $checkboxField->options($model, $field);

        expect($options)->toBe($field['options']);
    });

    test('options method returns empty array when no options defined', function () {
        $checkboxField = new Checkbox;
        $model = new CheckboxFieldModel;
        $field = ['slug' => 'checkbox'];

        $options = $checkboxField->options($model, $field);

        expect($options)->toBe([]);
    });

    test('options method uses model method when available', function () {
        $checkboxField = new Checkbox;
        $model = new CheckboxFieldModelWithDynamicOptions;
        $field = ['slug' => 'colors'];

        $options = $checkboxField->options($model, $field);

        expect($options)->toBe([
            ['key' => 'red', 'value' => 'Red'],
            ['key' => 'blue', 'value' => 'Blue'],
        ]);
    });
});
