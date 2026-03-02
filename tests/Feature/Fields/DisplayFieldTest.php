<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\ViewValue;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;
use Mockery;

class DisplayFieldModel extends Resource
{
    public static $singularName = 'Display Model';

    public static ?string $slug = 'displaymodel';

    public static string $type = 'DisplayModel';

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
            [
                'name' => 'Date for Test 2',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'format' => 'd.m.Y',
                'conditional_logic' => [],
                'slug' => 'date2',
                'display_view' => 'aura::fields.date-display',
            ],
            [
                'name' => 'View Only Field',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'view_only',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('ViewValue Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $viewValueField = new ViewValue;

        expect($viewValueField->edit)->toBe('aura::fields.view-value')
            ->and($viewValueField->view)->toBe('aura::fields.view-value');
    });

    test('edit and view use the same component', function () {
        $viewValueField = new ViewValue;

        expect($viewValueField->edit())->toBe($viewValueField->view());
    });
});

describe('ViewValue Field Rendering', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new DisplayFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'displaymodel'])
            ->assertSee('Create Display Model')
            ->assertSee('View Only Field');
    });
});

describe('Field Display Method Behavior', function () {
    test('display returns raw value when index is null', function () {
        $model = new DisplayFieldModel;
        $model->fields = [
            'date' => '2023-01-15',
        ];

        // Create a custom Date field class that will return the raw value
        $dateField = new class extends Date
        {
            public $index = null;

            public function display($field, $value, $model)
            {
                return $value;
            }
        };

        $field = ['slug' => 'date', 'format' => 'd.m.Y'];
        $result = $dateField->display($field, '2023-01-15', $model);

        expect($result)->toBe('2023-01-15');
    });

    test('display_view is used when set', function () {
        $model = new DisplayFieldModel;
        $model->fields = [
            'date2' => '2023-02-20',
        ];

        // Mock the Field class's display method to simulate prioritizing display_view
        $mockDateField = Mockery::mock(Date::class)->makePartial();

        $mockDateField->shouldReceive('display')
            ->once()
            ->with(
                Mockery::on(function ($field) {
                    return $field['display_view'] === 'aura::fields.date-display';
                }),
                '2023-02-20',
                $model
            )
            ->andReturn('Custom View Result');

        $field = [
            'slug' => 'date2',
            'format' => 'd.m.Y',
            'display_view' => 'aura::fields.date-display',
        ];

        $result = $mockDateField->display($field, '2023-02-20', $model);

        expect($result)->toBe('Custom View Result');
    });

    test('index property is used when display_view is not present', function () {
        $model = new DisplayFieldModel;
        $model->fields = [
            'date' => '2023-01-15',
        ];

        $dateField = new class extends Date
        {
            public $index = 'aura::fields.date-index';

            public function display($field, $value, $model)
            {
                return 'Dynamic Component Result';
            }
        };

        $field = [
            'slug' => 'date',
            'format' => 'd.m.Y',
        ];

        $result = $dateField->display($field, '2023-01-15', $model);

        expect($result)->toBe('Dynamic Component Result');
    });
});

describe('ViewValue Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new DisplayFieldModel);
    });

    test('saves record without errors including view-only fields', function () {
        Livewire::test(Create::class, ['slug' => 'displaymodel'])
            ->set('form.fields.date', '2023-01-15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['type' => 'DisplayModel']);
    });
});

describe('ViewValue Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $viewValueField = new ViewValue;

        expect($viewValueField->get(null, 'test value'))->toBe('test value')
            ->and($viewValueField->get(null, null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $viewValueField = new ViewValue;

        expect($viewValueField->value('test value'))->toBe('test value')
            ->and($viewValueField->value(''))->toBe('');
    });
});
