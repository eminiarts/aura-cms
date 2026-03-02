<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Number;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class NumberFieldModel extends Resource
{
    public static $singularName = 'Number Model';

    public static ?string $slug = 'number-model';

    public static string $type = 'NumberModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Number for Test',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric|nullable',
                'conditional_logic' => [],
                'suffix' => '%',
                'prefix' => 'CHF',
                'slug' => 'number',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Number Field Configuration', function () {
    test('has required configuration fields', function () {
        $numberField = new Number;
        $fields = collect($numberField->getFields());

        expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'prefix'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'suffix'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $numberField = new Number;

        expect($numberField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $numberField = new Number;

        expect($numberField->edit)->toBe('aura::fields.number')
            ->and($numberField->view)->toBe('aura::fields.view-value');
    });

    test('has integer table column type', function () {
        $numberField = new Number;

        expect($numberField->tableColumnType)->toBe('integer');
    });

    test('edit method returns edit property', function () {
        $numberField = new Number;

        expect($numberField->edit())->toBe('aura::fields.number');
    });

    test('view method returns view property', function () {
        $numberField = new Number;

        expect($numberField->view())->toBe('aura::fields.view-value');
    });
});

describe('Number Field Rendering', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new NumberFieldModel);
    });

    test('renders in create form with correct type', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->assertSee('Create Number Model')
            ->assertSee('Number for Test')
            ->assertSeeHtml('type="number"');
    });

    test('renders prefix and suffix', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->assertSee('CHF')
            ->assertSee('%');
    });
});

describe('Number Field Validation', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new NumberFieldModel);
    });

    test('accepts null value when nullable', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->call('save')
            ->assertHasNoErrors(['form.fields.number']);

        $this->assertDatabaseHas('posts', ['type' => 'NumberModel']);

        $model = NumberFieldModel::first();
        expect($model->fields['number'])->toBeNull();
    });

    test('rejects non-numeric value', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', 'not-a-number')
            ->call('save')
            ->assertHasErrors(['form.fields.number']);
    });

    test('rejects date string as number', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '2021-01-01')
            ->call('save')
            ->assertHasErrors(['form.fields.number']);
    });

    test('accepts valid numeric string', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '5')
            ->call('save')
            ->assertHasNoErrors(['form.fields.number']);
    });
});

describe('Number Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new NumberFieldModel);
    });

    test('saves number to database', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '42')
            ->call('save')
            ->assertHasNoErrors();

        $model = NumberFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['number'])->toBe('42')
            ->and($model->number)->toBe('42');
    });

    test('saves zero value', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '0')
            ->call('save')
            ->assertHasNoErrors();

        $model = NumberFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['number'])->toBe('0');
    });

    test('saves negative number', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '-10')
            ->call('save')
            ->assertHasNoErrors();

        $model = NumberFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['number'])->toBe('-10');
    });

    test('saves decimal number', function () {
        Livewire::test(Create::class, ['slug' => 'number-model'])
            ->set('form.fields.number', '3.14')
            ->call('save')
            ->assertHasNoErrors();

        $model = NumberFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['number'])->toBe('3.14');
    });
});

describe('Number Field Value Handling', function () {
    test('set method returns value unchanged', function () {
        $numberField = new Number;

        expect($numberField->set(null, [], '42'))->toBe('42')
            ->and($numberField->set(null, [], '0'))->toBe('0')
            ->and($numberField->set(null, [], null))->toBeNull();
    });

    test('value method casts to integer', function () {
        $numberField = new Number;

        expect($numberField->value('42'))->toBe(42)
            ->and($numberField->value('3.14'))->toBe(3)
            ->and($numberField->value('0'))->toBe(0)
            ->and($numberField->value('-10'))->toBe(-10);
    });

    test('filterOptions returns numeric filters', function () {
        $numberField = new Number;
        $options = $numberField->filterOptions();

        expect($options)->toHaveKey('equals')
            ->and($options)->toHaveKey('not_equals')
            ->and($options)->toHaveKey('greater_than')
            ->and($options)->toHaveKey('less_than')
            ->and($options)->toHaveKey('greater_than_or_equal')
            ->and($options)->toHaveKey('less_than_or_equal')
            ->and($options)->toHaveKey('is_empty')
            ->and($options)->toHaveKey('is_not_empty');
    });

    test('getFilterValues returns min and max from field config', function () {
        $numberField = new Number;

        $field = ['min' => 0, 'max' => 100];
        $values = $numberField->getFilterValues(null, $field);

        expect($values)->toHaveKey('min')
            ->and($values['min'])->toBe(0)
            ->and($values)->toHaveKey('max')
            ->and($values['max'])->toBe(100);
    });

    test('getFilterValues returns null when min/max not defined', function () {
        $numberField = new Number;

        $field = [];
        $values = $numberField->getFilterValues(null, $field);

        expect($values['min'])->toBeNull()
            ->and($values['max'])->toBeNull();
    });
});
