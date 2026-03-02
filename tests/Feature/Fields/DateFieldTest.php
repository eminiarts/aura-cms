<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Date;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class DateFieldModel extends Resource
{
    public static $singularName = 'Date Model';

    public static ?string $slug = 'datemodel';

    public static string $type = 'DateModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Date for Test',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'format' => 'd.m.Y',
                'conditional_logic' => [],
                'slug' => 'date',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Date Field Configuration', function () {
    test('has required configuration fields', function () {
        $dateField = new Date;
        $fields = collect($dateField->getFields());

        expect($fields->firstWhere('slug', 'date'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'format'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'display_format'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'enable_input'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'maxDate'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'weekStartsOn'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $dateField = new Date;

        expect($dateField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $dateField = new Date;

        expect($dateField->edit)->toBe('aura::fields.date')
            ->and($dateField->view)->toBe('aura::fields.view-value')
            ->and($dateField->index)->toBe('aura::fields.date-index');
    });

    test('has date table column type', function () {
        $dateField = new Date;

        expect($dateField->tableColumnType)->toBe('date');
    });

    test('format field has default value', function () {
        $dateField = new Date;
        $fields = collect($dateField->getFields());

        $formatField = $fields->firstWhere('slug', 'format');
        expect($formatField['default'])->toBe('d.m.Y');
    });

    test('weekStartsOn field has Monday as default', function () {
        $dateField = new Date;
        $fields = collect($dateField->getFields());

        $weekStartsOnField = $fields->firstWhere('slug', 'weekStartsOn');
        expect($weekStartsOnField['default'])->toBe(1);
    });

    test('enable_input field has true as default', function () {
        $dateField = new Date;
        $fields = collect($dateField->getFields());

        $enableInputField = $fields->firstWhere('slug', 'enable_input');
        expect($enableInputField['default'])->toBe(true);
    });
});

describe('Date Field Rendering', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new DateFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'datemodel'])
            ->assertSee('Create Date Model')
            ->assertSee('Date for Test');
    });

    test('renders with date picker icon', function () {
        Livewire::test(Create::class, ['slug' => 'datemodel'])
            ->assertSeeHtml('<svg class="w-5 h-5 text-gray-400"');
    });
});

describe('Date Field in Livewire', function () {
    beforeEach(function () {
        $this->withoutExceptionHandling();
        Aura::fake();
        Aura::setModel(new DateFieldModel);
    });

    test('saves date value to database', function () {
        Livewire::test(Create::class, ['slug' => 'datemodel'])
            ->set('form.fields.date', '2021-01-01')
            ->call('save')
            ->assertHasNoErrors(['form.fields.date']);

        $model = DateFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['date'])->toBe('2021-01-01')
            ->and($model->date)->toBe('2021-01-01');
    });

    test('saves null when date not provided', function () {
        Livewire::test(Create::class, ['slug' => 'datemodel'])
            ->call('save')
            ->assertHasNoErrors(['form.fields.date']);

        $this->assertDatabaseHas('posts', ['type' => 'DateModel']);

        $model = DateFieldModel::first();
        expect($model->fields['date'])->toBeNull();
    });
});

describe('Date Field via HTTP', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new DateFieldModel);
    });

    test('renders create page', function () {
        $this->actingAs($this->user)
            ->get('/admin/datemodel/create')
            ->assertOk()
            ->assertSee('Date for Test')
            ->assertSeeLivewire('aura.base.livewire.resource.create');
    });
});

describe('Date Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $dateField = new Date;

        expect($dateField->get(null, '2021-01-01'))->toBe('2021-01-01')
            ->and($dateField->get(null, null))->toBeNull();
    });

    test('set method returns value unchanged', function () {
        $dateField = new Date;

        expect($dateField->set(null, [], '2021-01-01'))->toBe('2021-01-01')
            ->and($dateField->set(null, [], null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $dateField = new Date;

        expect($dateField->value('2021-01-01'))->toBe('2021-01-01');
    });

    test('filterOptions returns date-specific filters', function () {
        $dateField = new Date;
        $options = $dateField->filterOptions();

        expect($options)->toHaveKey('date_is')
            ->and($options)->toHaveKey('date_is_not')
            ->and($options)->toHaveKey('date_before')
            ->and($options)->toHaveKey('date_after')
            ->and($options)->toHaveKey('date_on_or_before')
            ->and($options)->toHaveKey('date_on_or_after')
            ->and($options)->toHaveKey('date_is_empty')
            ->and($options)->toHaveKey('date_is_not_empty');
    });
});
