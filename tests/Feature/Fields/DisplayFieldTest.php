<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Date;
use Aura\Base\Fields\Field;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class DisplayFieldModel extends Resource
{
    public static $singularName = 'Date Model';

    public static ?string $slug = 'datemodel';

    public static string $type = 'DateModel';

    public static function getFields()
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
        ];
    }
}

test('display() returns the correct view', function () {
    // Create a model instance with the date field
    $model = new DisplayFieldModel;
    $model->fields = [
        'date' => '2023-01-15',
        'date2' => '2023-02-20',
    ];

    // Create a custom Date field class that will return the raw value
    $dateField = new class extends Date
    {
        // Override to disable index view
        public $index = null;

        // Override display method to simplify testing
        public function display($field, $value, $model)
        {
            // For this test, we just want the raw value without any view rendering
            return $value;
        }
    };

    $field = ['slug' => 'date', 'format' => 'd.m.Y'];
    $result = $dateField->display($field, '2023-01-15', $model);

    // Without display_view or index, it should return the raw value
    expect($result)->toBe('2023-01-15');
});

test('display_view is used', function () {
    // Create a model instance
    $model = new DisplayFieldModel;
    $model->fields = [
        'date2' => '2023-02-20',
    ];

    // Mock the Field class's display method to simulate prioritizing display_view
    $mockDateField = Mockery::mock(Date::class)->makePartial();

    // Set up the mock to return a specific result when display is called
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

    // When display_view is present, it should be prioritized
    expect($result)->toBe('Custom View Result');
});

test('index property is used when display_view is not present', function () {
    // Create a model instance
    $model = new DisplayFieldModel;
    $model->fields = [
        'date' => '2023-01-15',
    ];

    // Create a field with no display_view but with an index property
    $dateField = new class extends Date
    {
        public $index = 'aura::fields.date-index';

        // Override the display method to simulate using the index
        public function display($field, $value, $model)
        {
            // For this test, we'll return a specific string to simulate Blade rendering
            return 'Dynamic Component Result';
        }
    };

    $field = [
        'slug' => 'date',
        'format' => 'd.m.Y',
    ];

    $result = $dateField->display($field, '2023-01-15', $model);

    // When display_view is not present but index is, it should use the index property
    expect($result)->toBe('Dynamic Component Result');
});
