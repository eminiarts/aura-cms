<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Select;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class SelectFieldModel extends Resource
{
    public static $singularName = 'Select Model';

    public static ?string $slug = 'selectmodel';

    public static string $type = 'SelectModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Select for Test',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'choice',
                'options' => [
                    'a' => 'Alpha',
                    'b' => 'Beta',
                ],
            ],
        ];
    }
}

class SelectWithMethodModel extends Resource
{
    public static ?string $slug = 'selectmethodmodel';

    public static string $type = 'SelectMethodModel';

    public function getChoiceOptions()
    {
        return ['x' => 'X option', 'y' => 'Y option'];
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Choice',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'choice',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Select Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Select;

        expect($field->optionGroup)->toBe('Choice Fields')
            ->and($field->edit)->toBe('aura::fields.select')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->index)->toBe('aura::fields.select-index');
    });

    test('has options and allow_multiple configuration fields', function () {
        $fields = collect((new Select)->getFields());

        expect($fields->firstWhere('slug', 'options'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'allow_multiple'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('filterOptions returns choice-specific filters', function () {
        $options = (new Select)->filterOptions();

        expect($options)->toHaveKeys(['is', 'is_not', 'is_empty', 'is_not_empty']);
    });
});

describe('Select Field Options Resolution', function () {
    test('options returns field-defined options', function () {
        $field = new Select;
        $definition = ['slug' => 'choice', 'options' => ['a' => 'Alpha']];

        expect($field->options(new SelectFieldModel, $definition))->toBe(['a' => 'Alpha']);
    });

    test('options prefers a model getXxxOptions method when present', function () {
        $field = new Select;
        $definition = ['slug' => 'choice', 'options' => ['a' => 'Alpha']];

        expect($field->options(new SelectWithMethodModel, $definition))
            ->toBe(['x' => 'X option', 'y' => 'Y option']);
    });

    test('getFilterValues delegates to options', function () {
        $field = new Select;
        $definition = ['slug' => 'choice', 'options' => ['a' => 'Alpha']];

        expect($field->getFilterValues(new SelectFieldModel, $definition))->toBe(['a' => 'Alpha']);
    });
});

describe('Select Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new SelectFieldModel);
    });

    test('renders options in create form', function () {
        Livewire::test(Create::class, ['slug' => 'selectmodel'])
            ->assertOk()
            ->assertSee('Select for Test')
            ->assertSee('Alpha')
            ->assertSee('Beta');
    });

    test('saves selected option round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'selectmodel'])
            ->set('form.fields.choice', 'b')
            ->call('save')
            ->assertHasNoErrors(['form.fields.choice']);

        $model = SelectFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['choice'])->toBe('b');
    });
});
