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

test('fields get grouped when field group is true', function () {
    $model = new ApplyWrappersModel;

    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1); // Because of the wrapper

    expect($fields[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id',
        'conditional_logic', 'fields',
    ]);

    expect($fields[0]['label'])->toBe('TabPills');
    expect($fields[0]['type'])->toBe('TabPills');
    expect($fields[0]['slug'])->toBe('tabpills');
    expect($fields[0]['_id'])->toBe(1);
    expect($fields[0]['_parent_id'])->toBeNull();

    // Check the nested fields array
    expect($fields[0]['fields'])->toHaveCount(2);

    // Check first tabpill
    expect($fields[0]['fields'][0]['label'])->toBe('Tabpill 1');
    expect($fields[0]['fields'][0]['type'])->toBe('TabPill');
    expect($fields[0]['fields'][0]['field_type'])->toBe('tab');
    expect($fields[0]['fields'][0]['_parent_id'])->toBe(1);
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);

    // Check text field inside first tabpill
    expect($fields[0]['fields'][0]['fields'][0]['label'])->toBe('Text 1');
    expect($fields[0]['fields'][0]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Text');
    expect($fields[0]['fields'][0]['fields'][0]['field_type'])->toBe('input');
    expect($fields[0]['fields'][0]['fields'][0]['_parent_id'])->toBe(2);

    // Check second tabpill
    expect($fields[0]['fields'][1]['label'])->toBe('Tabpill 2');
    expect($fields[0]['fields'][1]['type'])->toBe('TabPill');
    expect($fields[0]['fields'][1]['field_type'])->toBe('tab');
    expect($fields[0]['fields'][1]['_parent_id'])->toBe(1);
    expect($fields[0]['fields'][1]['fields'])->toHaveCount(1);

    // Check text field inside second tabpill
    expect($fields[0]['fields'][1]['fields'][0]['label'])->toBe('Text 2');
    expect($fields[0]['fields'][1]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Text');
    expect($fields[0]['fields'][1]['fields'][0]['field_type'])->toBe('input');
    expect($fields[0]['fields'][1]['fields'][0]['_parent_id'])->toBe(4);
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
                // 'global' => true,
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

test('fields get wrapped when field wrapper is set', function () {

    $model = new ApplyWrappersModel2;

    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1); // Because of the wrapper

    expect($fields[0]['fields'])->toHaveCount(1); // Because of global

});
