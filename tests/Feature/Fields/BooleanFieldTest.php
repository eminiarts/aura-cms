<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class BooleanFieldModel extends Resource
{
    public static $singularName = 'Boolean Model';

    public static ?string $slug = 'boolean';

    public static string $type = 'BooleanModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Boolean for Test',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => 'boolean',
                'conditional_logic' => [],
                'slug' => 'boolean',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Boolean Field Configuration', function () {
    test('has correct option group', function () {
        $booleanField = new Boolean;

        expect($booleanField->optionGroup)->toBe('Choice Fields');
    });

    test('has correct edit and view properties', function () {
        $booleanField = new Boolean;

        expect($booleanField->edit)->toBe('aura::fields.boolean')
            ->and($booleanField->view)->toBe('aura::fields.view-value');
    });

    test('has default value configuration field', function () {
        $booleanField = new Boolean;
        $fields = collect($booleanField->getFields());

        $defaultField = $fields->firstWhere('slug', 'default');
        expect($defaultField)->not->toBeNull()
            ->and($defaultField['type'])->toBe('Aura\\Base\\Fields\\Boolean')
            ->and($defaultField['default'])->toBe(false);
    });

    test('edit method returns edit property', function () {
        $booleanField = new Boolean;

        expect($booleanField->edit())->toBe('aura::fields.boolean');
    });

    test('view method returns view property', function () {
        $booleanField = new Boolean;

        expect($booleanField->view())->toBe('aura::fields.view-value');
    });
});

describe('Boolean Field Rendering', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new BooleanFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'boolean'])
            ->assertSee('Create Boolean Model')
            ->assertSee('Boolean for Test')
            ->assertSeeHtml('<button x-ref="toggle"');
    });

    test('renders toggle button styles', function () {
        Livewire::test(Create::class, ['slug' => 'boolean'])
            ->assertSeeHtml('bg-gray-300')
            ->assertSeeHtml('bg-primary-600');
    });
});

describe('Boolean Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new BooleanFieldModel);
    });

    test('saves false by default', function () {
        Livewire::test(Create::class, ['slug' => 'boolean'])
            ->call('save')
            ->assertHasNoErrors(['form.fields.boolean']);

        $this->assertDatabaseHas('posts', ['type' => 'BooleanModel']);

        $model = BooleanFieldModel::first();
        expect($model->fields['boolean'])->toBeFalse();
    });

    test('saves true value', function () {
        Livewire::test(Create::class, ['slug' => 'boolean'])
            ->set('form.fields.boolean', true)
            ->call('save')
            ->assertHasNoErrors();

        $model = BooleanFieldModel::first();
        expect($model->fields['boolean'])->toBeTrue();
    });

    test('saves false value explicitly', function () {
        Livewire::test(Create::class, ['slug' => 'boolean'])
            ->set('form.fields.boolean', false)
            ->call('save')
            ->assertHasNoErrors();

        $model = BooleanFieldModel::first();
        expect($model->fields['boolean'])->toBeFalse();
    });
});

describe('Boolean Field Value Handling', function () {
    test('get method casts to boolean', function () {
        $booleanField = new Boolean;

        expect($booleanField->get(null, true))->toBeTrue()
            ->and($booleanField->get(null, false))->toBeFalse()
            ->and($booleanField->get(null, 1))->toBeTrue()
            ->and($booleanField->get(null, 0))->toBeFalse()
            ->and($booleanField->get(null, '1'))->toBeTrue()
            ->and($booleanField->get(null, '0'))->toBeFalse()
            ->and($booleanField->get(null, null))->toBeFalse();
    });

    test('set method casts to boolean', function () {
        $booleanField = new Boolean;

        expect($booleanField->set(null, [], true))->toBeTrue()
            ->and($booleanField->set(null, [], false))->toBeFalse()
            ->and($booleanField->set(null, [], 1))->toBeTrue()
            ->and($booleanField->set(null, [], 0))->toBeFalse();
    });

    test('value method casts to boolean', function () {
        $booleanField = new Boolean;

        expect($booleanField->value(true))->toBeTrue()
            ->and($booleanField->value(false))->toBeFalse()
            ->and($booleanField->value(1))->toBeTrue()
            ->and($booleanField->value(0))->toBeFalse()
            ->and($booleanField->value('1'))->toBeTrue()
            ->and($booleanField->value(''))->toBeFalse();
    });
});

describe('Boolean Field Display', function () {
    test('displays check icon for true value', function () {
        $booleanField = new Boolean;
        $field = ['slug' => 'boolean'];
        $model = new BooleanFieldModel;

        $result = $booleanField->display($field, true, $model);

        expect($result)->toContain('svg')
            ->and($result)->toContain('M5 13l4 4L19 7');
    });

    test('displays x icon for false value', function () {
        $booleanField = new Boolean;
        $field = ['slug' => 'boolean'];
        $model = new BooleanFieldModel;

        $result = $booleanField->display($field, false, $model);

        expect($result)->toContain('svg')
            ->and($result)->toContain('M6 18L18 6M6 6l12 12')
            ->and($result)->toContain('text-gray-200');
    });
});
