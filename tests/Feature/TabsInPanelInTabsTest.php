<?php

use Aura\Base\Resource;

class TabsInPanelInTabsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
            ],
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
                'wrap' => true,
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
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
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

test('root structure has single Tabs wrapper', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('root Tabs has two global tabs', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2');
});

test('first global tab contains panel', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][0]['type'])->toBe('Aura\Base\Fields\Panel');
});

test('panel inside first tab has nested Tabs wrapper', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $panel = $fields[0]['fields'][0]['fields'][0];

    expect($panel['fields'])->toHaveCount(1)
        ->and($panel['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('nested Tabs has two tabs inside panel', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $nestedTabs = $fields[0]['fields'][0]['fields'][0]['fields'][0];

    expect($nestedTabs['fields'])->toHaveCount(2)
        ->and($nestedTabs['fields'][0]['name'])->toBe('Tab 1 in Panel')
        ->and($nestedTabs['fields'][1]['name'])->toBe('Tab 2 in Panel');
});

test('nested tabs contain text fields', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $nestedTabs = $fields[0]['fields'][0]['fields'][0]['fields'][0];

    expect($nestedTabs['fields'][0]['fields'][0]['slug'])->toBe('text1')
        ->and($nestedTabs['fields'][1]['fields'][0]['slug'])->toBe('text2');
});

test('second global tab contains text field', function () {
    $model = new TabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];

    expect($tab2['fields'][0]['slug'])->toBe('text-2-1')
        ->and($tab2['fields'][0]['type'])->toBe('Aura\Base\Fields\Text');
});
