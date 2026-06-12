<?php

use Aura\Base\Resource;

class MultipleTabsInPanelInTabsTestModelWithAnotherPanel extends Resource
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
                'exclude_level' => 4,
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

test('root structure has single Tabs wrapper with two global tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2');
});

test('Panel 1 and Panel 2 are at same level in Tab 1', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][1]['name'])->toBe('Panel 2');
});

test('Panel 1 contains nested Tabs with two tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];

    expect($panel1['fields'])->toHaveCount(1)
        ->and($panel1['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($panel1['fields'][0]['fields'])->toHaveCount(2)
        ->and($panel1['fields'][0]['fields'][0]['name'])->toBe('Tab 1 in Panel')
        ->and($panel1['fields'][0]['fields'][1]['name'])->toBe('Tab 2 in Panel');
});

test('Panel 2 uses exclude_level 4 to be sibling of Panel 1', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;
    $fields = $model->getGroupedFields();

    $panel2 = $fields[0]['fields'][0]['fields'][1];

    expect($panel2['name'])->toBe('Panel 2')
        ->and($panel2['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($panel2['exclude_level'])->toBe(4);
});

test('Panel 2 contains nested Tabs with two tabs', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;
    $fields = $model->getGroupedFields();

    $panel2 = $fields[0]['fields'][0]['fields'][1];

    expect($panel2['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($panel2['fields'][0]['fields'])->toHaveCount(2)
        ->and($panel2['fields'][0]['fields'][0]['name'])->toBe('Tab 1 in Panel2')
        ->and($panel2['fields'][0]['fields'][1]['name'])->toBe('Tab 2 in Panel2');
});
