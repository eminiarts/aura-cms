<?php

use Aura\Base\Resource;

class PanelsInTabsModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
                'style' => [],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
                'style' => [],
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
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

test('panel 2 has correct parent ID pointing to Tab 2', function () {
    $model = new PanelsInTabsModel;
    $fields = $model->getFieldsWithIds();

    $panel2 = $fields->firstWhere('slug', 'panel-2');

    expect($panel2)->toBeArray()
        ->and($panel2['_parent_id'])->toBe(5);
});

test('root structure has single Tabs wrapper', function () {
    $model = new PanelsInTabsModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('Tabs wrapper contains two tabs', function () {
    $model = new PanelsInTabsModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2');
});

test('each tab contains one panel', function () {
    $model = new PanelsInTabsModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];
    $tab2 = $fields[0]['fields'][1];

    expect($tab1['fields'])->toHaveCount(1)
        ->and($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][0]['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($tab2['fields'])->toHaveCount(1)
        ->and($tab2['fields'][0]['name'])->toBe('Panel 2')
        ->and($tab2['fields'][0]['type'])->toBe('Aura\Base\Fields\Panel');
});
