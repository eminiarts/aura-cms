<?php

use Aura\Base\Resource;

class ApplyTabsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-2-1',
            ],
        ];
    }
}

test('tabs are wrapped in Tabs container', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    expect($tabs)->toHaveCount(1)
        ->and($tabs[0]['type'])->toBe('Aura\Base\Fields\Tabs')
        ->and($tabs[0]['label'])->toBe('Aura\Base\Fields\Tabs')
        ->and($tabs[0]['slug'])->toBe('aurabasefieldstabs');
});

test('tabs wrapper has correct structure', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    expect($tabs[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id',
        'conditional_logic', 'fields',
    ])
        ->and($tabs[0]['_id'])->toBe(1)
        ->and($tabs[0]['_parent_id'])->toBeNull();
});

test('tabs contain correct number of tab fields', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    expect($tabs[0]['fields'])->toHaveCount(2);
});

test('first tab has correct properties', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    $firstTab = $tabs[0]['fields'][0];

    expect($firstTab['label'])->toBe('Tab 1')
        ->and($firstTab['type'])->toBe('Aura\Base\Fields\Tab')
        ->and($firstTab['field_type'])->toBe('tab')
        ->and($firstTab['_parent_id'])->toBe(1)
        ->and($firstTab['fields'])->toHaveCount(1);
});

test('second tab has correct properties', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    $secondTab = $tabs[0]['fields'][1];

    expect($secondTab['label'])->toBe('Tab 2')
        ->and($secondTab['type'])->toBe('Aura\Base\Fields\Tab')
        ->and($secondTab['field_type'])->toBe('tab')
        ->and($secondTab['_parent_id'])->toBe(1)
        ->and($secondTab['fields'])->toHaveCount(1);
});

test('fields inside tabs have correct parent references', function () {
    $model = new ApplyTabsTestModel;
    $tabs = $model->getGroupedFields();

    $textFieldInTab1 = $tabs[0]['fields'][0]['fields'][0];
    $textFieldInTab2 = $tabs[0]['fields'][1]['fields'][0];

    expect($textFieldInTab1['slug'])->toBe('text-1-1')
        ->and($textFieldInTab1['_parent_id'])->toBe(2)
        ->and($textFieldInTab2['slug'])->toBe('text-2-1')
        ->and($textFieldInTab2['_parent_id'])->toBe(4);
});
