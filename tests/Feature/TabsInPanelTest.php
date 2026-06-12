<?php

use Aura\Base\Resource;

class TabsInPanelTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [

            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
            ],
            [
                'label' => 'Tab 1 in Panel',
                'name' => 'Tab 1 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-1',
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
                'label' => 'Tab 2 in Panel',
                'name' => 'Tab 2 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-2',
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

test('panel wraps tabs inside it', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['label'])->toBe('Panel 1')
        ->and($fields[0]['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($fields[0]['slug'])->toBe('panel')
        ->and($fields[0]['field_type'])->toBe('panel');
});

test('panel has correct structure', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id',
        'conditional_logic', 'fields',
    ])
        ->and($fields[0]['_id'])->toBe(1)
        ->and($fields[0]['_parent_id'])->toBeNull();
});

test('tabs wrapper is nested inside panel', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $tabsWrapper = $fields[0]['fields'][0];

    expect($fields[0]['fields'])->toHaveCount(1)
        ->and($tabsWrapper['label'])->toBe('Aura\Base\Fields\Tabs')
        ->and($tabsWrapper['type'])->toBe('Aura\Base\Fields\Tabs')
        ->and($tabsWrapper['slug'])->toBe('aurabasefieldstabs')
        ->and($tabsWrapper['_id'])->toBe(2)
        ->and($tabsWrapper['_parent_id'])->toBe(1);
});

test('tabs wrapper contains two tabs', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $tabsWrapper = $fields[0]['fields'][0];

    expect($tabsWrapper['fields'])->toHaveCount(2);
});

test('first tab in panel has correct properties', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $firstTab = $fields[0]['fields'][0]['fields'][0];

    expect($firstTab['label'])->toBe('Tab 1 in Panel')
        ->and($firstTab['type'])->toBe('Aura\Base\Fields\Tab')
        ->and($firstTab['field_type'])->toBe('tab')
        ->and($firstTab['_id'])->toBe(3)
        ->and($firstTab['_parent_id'])->toBe(2)
        ->and($firstTab['fields'])->toHaveCount(1);
});

test('text field inside first tab has correct properties', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $textField = $fields[0]['fields'][0]['fields'][0]['fields'][0];

    expect($textField['label'])->toBe('Text 1')
        ->and($textField['type'])->toBe('Aura\Base\Fields\Text')
        ->and($textField['field_type'])->toBe('input')
        ->and($textField['_id'])->toBe(4)
        ->and($textField['_parent_id'])->toBe(3);
});

test('second tab in panel has correct properties', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $secondTab = $fields[0]['fields'][0]['fields'][1];

    expect($secondTab['label'])->toBe('Tab 2 in Panel')
        ->and($secondTab['type'])->toBe('Aura\Base\Fields\Tab')
        ->and($secondTab['field_type'])->toBe('tab')
        ->and($secondTab['_id'])->toBe(5)
        ->and($secondTab['_parent_id'])->toBe(2)
        ->and($secondTab['fields'])->toHaveCount(1);
});

test('text field inside second tab has correct properties', function () {
    $model = new TabsInPanelTestModel;
    $fields = $model->getGroupedFields();

    $textField = $fields[0]['fields'][0]['fields'][1]['fields'][0];

    expect($textField['label'])->toBe('Text 2')
        ->and($textField['type'])->toBe('Aura\Base\Fields\Text')
        ->and($textField['field_type'])->toBe('input')
        ->and($textField['_id'])->toBe(6)
        ->and($textField['_parent_id'])->toBe(5);
});
