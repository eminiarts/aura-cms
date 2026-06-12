<?php

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Panel;
use Aura\Base\Resource;

class TabPills extends Field
{
    public $edit = 'fields.tabpills-edit';

    public bool $group = true;

    public bool $sameLevelGrouping = false;

    public string $type = 'tabpills';

    public $view = 'fields.tabpills-view';
}

class TabPill extends Field
{
    public $edit = 'fields.tabpill';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    public bool $sameLevelGrouping = true;

    public string $type = 'tab';

    public $view = 'fields.tabpill-view';

    public $wrapper = TabPills::class;
}

class ApplyWrappersModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [

            [
                'label' => 'Tabpill 1',
                'name' => 'Tabpill 1',
                'type' => TabPill::class,
                'slug' => 'tabpill-1',
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'label' => 'Tabpill 2',
                'name' => 'Tabpill 2',
                'type' => TabPill::class,
                'slug' => 'tabpill-2',
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('custom wrapper wraps fields correctly', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['label'])->toBe('TabPills')
        ->and($fields[0]['type'])->toBe('TabPills')
        ->and($fields[0]['slug'])->toBe('tabpills');
});

test('wrapper has correct structure', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    expect($fields[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id',
        'conditional_logic', 'fields',
    ])
        ->and($fields[0]['_id'])->toBe(1)
        ->and($fields[0]['_parent_id'])->toBeNull();
});

test('wrapper contains two tabpills', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(2);
});

test('first tabpill has correct properties', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    $tabpill1 = $fields[0]['fields'][0];

    expect($tabpill1['label'])->toBe('Tabpill 1')
        ->and($tabpill1['type'])->toBe('TabPill')
        ->and($tabpill1['field_type'])->toBe('tab')
        ->and($tabpill1['_parent_id'])->toBe(1)
        ->and($tabpill1['fields'])->toHaveCount(1);
});

test('text field inside first tabpill has correct properties', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    $textField = $fields[0]['fields'][0]['fields'][0];

    expect($textField['label'])->toBe('Text 1')
        ->and($textField['type'])->toBe('Aura\Base\Fields\Text')
        ->and($textField['field_type'])->toBe('input')
        ->and($textField['_parent_id'])->toBe(2);
});

test('second tabpill has correct properties', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    $tabpill2 = $fields[0]['fields'][1];

    expect($tabpill2['label'])->toBe('Tabpill 2')
        ->and($tabpill2['type'])->toBe('TabPill')
        ->and($tabpill2['field_type'])->toBe('tab')
        ->and($tabpill2['_parent_id'])->toBe(1)
        ->and($tabpill2['fields'])->toHaveCount(1);
});

test('text field inside second tabpill has correct properties', function () {
    $model = new ApplyWrappersModel;
    $fields = $model->getGroupedFields();

    $textField = $fields[0]['fields'][1]['fields'][0];

    expect($textField['label'])->toBe('Text 2')
        ->and($textField['type'])->toBe('Aura\Base\Fields\Text')
        ->and($textField['field_type'])->toBe('input')
        ->and($textField['_parent_id'])->toBe(4);
});

class ApplyWrappersModel2 extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [

            [
                'label' => 'Tabpill 1',
                'name' => 'Tabpill 1',
                'type' => TabPill::class,
                'slug' => 'tabpill-1',
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => Panel::class,
                'slug' => 'panel-1',
            ],
            [
                'label' => 'Tabpill 2',
                'name' => 'Tabpill 2',
                'type' => TabPill::class,
                'slug' => 'tabpill-2',
                'wrap' => true,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('wrap true creates new wrapper', function () {
    $model = new ApplyWrappersModel2;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1);
});

test('tabpill with wrap true is correctly wrapped', function () {
    $model = new ApplyWrappersModel2;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(1);
});
