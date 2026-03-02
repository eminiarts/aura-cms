<?php

use Aura\Base\Resource;

class PanelInTabsModel extends Resource
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
                'global' => true,
                'style' => [],
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
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
                'global' => true,
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

test('root structure has single Tabs wrapper', function () {
    $model = new PanelInTabsModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('Tabs wrapper contains two tabs', function () {
    $model = new PanelInTabsModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2');
});

test('first tab contains panel of correct type', function () {
    $model = new PanelInTabsModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][0]['type'])->toBe('Aura\Base\Fields\Panel');
});

test('panel in first tab contains text field', function () {
    $model = new PanelInTabsModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['type'])->toBe('Aura\Base\Fields\Tab')
        ->and($tab1['fields'])->toHaveCount(1)
        ->and($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][0]['fields'])->toHaveCount(1);
});

test('second tab contains text field directly', function () {
    $model = new PanelInTabsModel;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];

    expect($tab2['name'])->toBe('Tab 2')
        ->and($tab2['fields'])->toHaveCount(1)
        ->and($tab2['fields'][0]['slug'])->toBe('text-2-1');
});
