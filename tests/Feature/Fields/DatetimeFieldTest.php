<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Datetime;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class DatetimeFieldModel extends Resource
{
    public static $singularName = 'Datetime Model';

    public static ?string $slug = 'datetimemodel';

    public static string $type = 'DatetimeModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Datetime for Test',
                'type' => 'Aura\\Base\\Fields\\Datetime',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'datetime',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Datetime Field Configuration', function () {
    test('has correct option group', function () {
        expect((new Datetime)->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $field = new Datetime;

        expect($field->edit)->toBe('aura::fields.datetime')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->edit())->toBe('aura::fields.datetime')
            ->and($field->view())->toBe('aura::fields.view-value');
    });

    test('uses timestamp column type', function () {
        expect((new Datetime)->tableColumnType)->toBe('timestamp');
    });

    test('has required configuration fields with defaults', function () {
        $fields = collect((new Datetime)->getFields());

        expect($fields->firstWhere('slug', 'format'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'format')['default'])->toBe('d.m.Y H:i')
            ->and($fields->firstWhere('slug', 'display_format'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'display_format')['default'])->toBe('d.m.Y H:i')
            ->and($fields->firstWhere('slug', 'enable_input'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'enable_input')['default'])->toBe(true)
            ->and($fields->firstWhere('slug', 'weekStartsOn'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'weekStartsOn')['default'])->toBe(1);
    });

    test('filterOptions returns datetime-specific filters', function () {
        $options = (new Datetime)->filterOptions();

        expect($options)->toHaveKeys([
            'is', 'is_not', 'before', 'after', 'on_or_before', 'on_or_after', 'is_empty', 'is_not_empty',
        ]);
    });
});

describe('Datetime Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $field = new Datetime;

        expect($field->get(null, '2021-01-01 12:30'))->toBe('2021-01-01 12:30')
            ->and($field->get(null, null))->toBeNull();
    });

    test('set method returns value unchanged', function () {
        $field = new Datetime;

        expect($field->set(null, [], '2021-01-01 12:30'))->toBe('2021-01-01 12:30')
            ->and($field->set(null, [], null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        expect((new Datetime)->value('2021-01-01 12:30'))->toBe('2021-01-01 12:30');
    });
});

describe('Datetime Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new DatetimeFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'datetimemodel'])
            ->assertOk()
            ->assertSee('Datetime for Test');
    });

    test('saves datetime value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'datetimemodel'])
            ->set('form.fields.datetime', '2021-01-01 12:30')
            ->call('save')
            ->assertHasNoErrors(['form.fields.datetime']);

        $model = DatetimeFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['datetime'])->toBe('2021-01-01 12:30')
            ->and($model->datetime)->toBe('2021-01-01 12:30');
    });

    test('saves null when datetime not provided', function () {
        Livewire::test(Create::class, ['slug' => 'datetimemodel'])
            ->call('save')
            ->assertHasNoErrors(['form.fields.datetime']);

        expect(DatetimeFieldModel::first()->fields['datetime'])->toBeNull();
    });
});
