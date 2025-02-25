<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Date;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

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
                'display_view' => 'aura::fields.view-value',
            ],
        ];
    }
}

test('display() returns the correct view', function () {
    // Create a model instance with the date field
    $model = new DisplayFieldModel();
    $model->fields = [
        'date' => '2023-01-15',
        'date2' => '2023-02-20'
    ];
    
    // Test with no display_view or index - should return raw value
    $dateField = new Date();
    $field = ['slug' => 'date', 'format' => 'd.m.Y'];
    $result = $dateField->display($field, '2023-01-15', $model);
    
    expect($result)->toBe('2023-01-15');
});

test('display_view is used', function () {
    // Create a model instance
    $model = new DisplayFieldModel();
    $model->fields = [
        'date2' => '2023-02-20'
    ];
    
    // Prepare a field with display_view
    $dateField = new Date();
    $field = [
        'slug' => 'date2', 
        'format' => 'd.m.Y',
        'display_view' => 'aura::fields.view-value'
    ];
    
    // Mock the view method to verify it's called with the correct arguments
    $viewMock = $this->getMockBuilder('stdClass')
        ->addMethods(['render'])
        ->getMock();
    $viewMock->expects($this->once())
        ->method('render')
        ->willReturn('Custom View Result');
    
    // Use a partial mock of the Date field to intercept the view call
    $dateFieldMock = $this->getMockBuilder(Date::class)
        ->onlyMethods(['view'])
        ->getMock();
    $dateFieldMock->expects($this->once())
        ->method('view')
        ->with($field['display_view'], [
            'row' => $model,
            'field' => $field,
            'value' => '2023-02-20'
        ])
        ->willReturn($viewMock);
    
    $result = $dateFieldMock->display($field, '2023-02-20', $model);
    expect($result)->toBe('Custom View Result');
});

test('index property is used when display_view is not present', function () {
    // Create a model instance
    $model = new DisplayFieldModel();
    $model->fields = [
        'date' => '2023-01-15'
    ];
    
    // Create a field with no display_view but with an index property
    $dateField = new class extends Date {
        public $index = 'aura::fields.date';
    };
    
    $field = [
        'slug' => 'date', 
        'format' => 'd.m.Y'
    ];
    
    // Mock Blade::render to verify it's called correctly
    Blade::shouldReceive('render')
        ->once()
        ->with(
            '<x-dynamic-component :component="$componentName" :row="$row" :field="$field" :value="$value" />',
            [
                'componentName' => 'aura::fields.date',
                'row' => $model,
                'field' => $field,
                'value' => '2023-01-15',
            ]
        )
        ->andReturn('Dynamic Component Result');
    
    $result = $dateField->display($field, '2023-01-15', $model);
    expect($result)->toBe('Dynamic Component Result');
});
