<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class TextFieldModel extends Resource
{
    public static string $type = 'TextModel';

    public static ?string $slug = 'textmodel';

    public static $singularName = 'Text Model';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Text for Test',
                'default' => 'Default for Test',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'text',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Text Field Configuration', function () {
    test('has required configuration fields', function () {
        $textField = new Text;
        $fields = collect($textField->getFields());

        expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'autocomplete'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'prefix'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'suffix'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'max_length'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $textField = new Text;

        expect($textField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $textField = new Text;

        expect($textField->edit)->toBe('aura::fields.text')
            ->and($textField->view)->toBe('aura::fields.view-value');
    });

    test('edit method returns edit property', function () {
        $textField = new Text;

        expect($textField->edit())->toBe('aura::fields.text');
    });

    test('view method returns view property', function () {
        $textField = new Text;

        expect($textField->view())->toBe('aura::fields.view-value');
    });
});

describe('Text Field Rendering', function () {
    test('renders field name as label', function () {
        $field = [
            'name' => 'Text for Test',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'text',
        ];

        $fieldClass = app($field['type']);
        $field['field'] = $fieldClass;

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('>Text for Test</label>');
    });

    test('renders placeholder attribute', function () {
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
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('placeholder="Placeholder for Test"');
    });

    test('renders prefix', function () {
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
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('Prefix for Test');
    });

    test('renders suffix', function () {
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
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('Suffix for Test');
    });
});

describe('Text Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new TextFieldModel);
    });

    test('sets default value on create', function () {
        expect(auth()->user()->isSuperAdmin())->toBeTrue();

        $component = Livewire::test(Create::class, ['slug' => 'textmodel']);

        $component
            ->assertSee('Text for Test')
            ->assertSeeHtml('wire:model="form.fields.text"')
            ->assertDontSee('Advanced Text')
            ->assertSet('form.fields.text', 'Default for Test');
    });

    test('saves text value to database', function () {
        $component = Livewire::test(Create::class, ['slug' => 'textmodel'])
            ->set('form.fields.text', 'My Test Value')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['type' => 'TextModel']);

        $model = TextFieldModel::first();
        expect($model->fields['text'])->toBe('My Test Value')
            ->and($model->text)->toBe('My Test Value');
    });

    test('can save empty value', function () {
        $component = Livewire::test(Create::class, ['slug' => 'textmodel'])
            ->set('form.fields.text', '')
            ->call('save')
            ->assertHasNoErrors();

        $model = TextFieldModel::first();
        expect($model->fields['text'])->toBe('');
    });
});

describe('Text Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $textField = new Text;

        expect($textField->get(null, 'test value'))->toBe('test value')
            ->and($textField->get(null, ''))->toBe('')
            ->and($textField->get(null, null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $textField = new Text;

        expect($textField->value('test value'))->toBe('test value')
            ->and($textField->value(''))->toBe('')
            ->and($textField->value(null))->toBeNull();
    });

    test('filterOptions returns correct string filters', function () {
        $textField = new Text;
        $options = $textField->filterOptions();

        expect($options)->toHaveKey('contains')
            ->and($options)->toHaveKey('does_not_contain')
            ->and($options)->toHaveKey('is')
            ->and($options)->toHaveKey('is_not')
            ->and($options)->toHaveKey('starts_with')
            ->and($options)->toHaveKey('ends_with')
            ->and($options)->toHaveKey('is_empty')
            ->and($options)->toHaveKey('is_not_empty');
    });
});
