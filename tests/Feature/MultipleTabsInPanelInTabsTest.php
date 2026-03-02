<?php

use Aura\Base\Resource;

class MultipleTabsInPanelInTabsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
            ],
            [
                'name' => 'Tab 1 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'name' => 'Tab 2 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab2-1',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel2',
                'same_level_grouping' => false,
            ],
            [
                'name' => 'Tab 1 in Panel2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-2',
                'wrap' => true,
            ],
            [
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text3',
            ],
            [
                'name' => 'Tab 2 in Panel2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab2-2',
            ],
            [
                'name' => 'Text 4',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text4',
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-2-1',
            ],
        ];
    }
}

test('panel 2 has correct parent ID in fields with IDs', function () {
    $model = new MultipleTabsInPanelInTabsTestModel;
    $fields = $model->getFieldsWithIds();
    $panel2 = $fields->where('slug', 'panel2')->first();

    expect($panel2)->toBeArray()
        ->and($panel2['_parent_id'])->toBe(7);
});

test('root structure has single Tabs wrapper with two tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2');
});

test('Panel 1 is nested inside Tab 1', function () {
    $model = new MultipleTabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'][0]['fields'][0]['name'])->toBe('Panel 1');
});

test('Panel 1 contains nested Tabs with two tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];

    expect($panel1['fields'])->toHaveCount(1)
        ->and($panel1['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($panel1['fields'][0]['fields'])->toHaveCount(2)
        ->and($panel1['fields'][0]['fields'][0]['name'])->toBe('Tab 1 in Panel')
        ->and($panel1['fields'][0]['fields'][1]['name'])->toBe('Tab 2 in Panel');
});

test('Panel 2 is nested inside Tab 2 of Panel 1 tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];
    $nestedTabs = $panel1['fields'][0];
    $tab2InPanel = $nestedTabs['fields'][1];

    expect($tab2InPanel['fields'][1]['name'])->toBe('Panel 2')
        ->and($tab2InPanel['fields'][1]['type'])->toBe('Aura\Base\Fields\Panel');
});
