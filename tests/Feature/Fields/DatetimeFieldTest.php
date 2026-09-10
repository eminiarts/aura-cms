<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Datetime;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

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
            'date_is', 'date_is_not', 'date_before', 'date_after', 'date_on_or_before', 'date_on_or_after', 'date_is_empty', 'date_is_not_empty',
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

    test('uses date-prefixed UI operators and supports saved datetime range operators', function () {
        $early = DatetimeFieldModel::create([
            'fields' => ['datetime' => '2026-03-01 08:00:00'],
            'title' => 'Early',
        ]);
        $middle = DatetimeFieldModel::create([
            'fields' => ['datetime' => '2026-03-02 08:00:00'],
            'title' => 'Middle',
        ]);
        $late = DatetimeFieldModel::create([
            'fields' => ['datetime' => '2026-03-03 08:00:00'],
            'title' => 'Late',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $early])
            ->call('addFilterGroup')
            ->set('filters.custom.0.filters.0.name', 'datetime');

        foreach ([
            ['date_before', '2026-03-02', [$early->id]],
            ['before', '2026-03-02', [$early->id]],
            ['after', '2026-03-02', [$late->id]],
            ['on_or_before', '2026-03-02', [$early->id, $middle->id]],
            ['on_or_after', '2026-03-02', [$middle->id, $late->id]],
        ] as [$operator, $value, $expectedIds]) {
            $component
                ->set('filters.custom.0.filters.0.operator', $operator)
                ->set('filters.custom.0.filters.0.value', $value)
                ->assertViewHas('rows', fn ($rows) => $rows->total() === count($expectedIds)
                    && $rows->getCollection()->pluck('id')->sort()->values()->all() === collect($expectedIds)->sort()->values()->all());
        }
    });
});
