<?php

use Aura\Base\Resource;

class ModelWithGroups extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'global' => true,
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'style' => [],
            ],
            [
                'label' => 'Text 1.1',
                'name' => 'Text 1.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'label' => 'Repeater 1.2',
                'name' => 'Repeater 1.2',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-1-2',
                'style' => [],
            ],
            [
                'label' => 'Text 1.2.1',
                'name' => 'Text 1.2.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-2-1',
            ],
            [
                'label' => 'Repeater 1.2.2',
                'name' => 'Repeater 1.2.2',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-1-2-2',
                'style' => [],
                'exclude_level' => 1,
            ],
            [
                'label' => 'Total 1.2.2.1',
                'name' => 'Total 1.2.2.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-1-2-2-1',
            ],
            [
                'label' => 'Panel 2',
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
                'exclude_level' => 2,
            ],
            [
                'label' => 'Repeater 2.1',
                'name' => 'Repeater 2.1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-2-1',
                'style' => [],
            ],
            [
                'label' => 'Repeater 2.1.1',
                'name' => 'Repeater 2.1.1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-2-1-1',
                'style' => [],
            ],
            [
                'label' => 'Total 2.1.1.1',
                'name' => 'Total 2.1.1.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-2-1-1-1',
            ],

            [
                'label' => 'Tab 2',
                'global' => true,
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Panel 3',
                'name' => 'Panel 3',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-3',
                'style' => [],
                'same_level_grouping' => false, // Override default true to allow tab 2
            ],
            [
                'label' => 'Text 3',
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text3',
            ],
        ];
    }
}

test('root structure has single Tabs wrapper', function () {
    $model = new ModelWithGroups;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1);
});

test('Tabs wrapper contains two tabs', function () {
    $model = new ModelWithGroups;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(2)
        ->and($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][0]['slug'])->toBe('tab-1');
});

test('Tab 1 contains two panels', function () {
    $model = new ModelWithGroups;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['fields'])->toHaveCount(2)
        ->and($tab1['fields'][0]['name'])->toBe('Panel 1')
        ->and($tab1['fields'][0]['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($tab1['fields'][1]['name'])->toBe('Panel 2')
        ->and($tab1['fields'][1]['type'])->toBe('Aura\Base\Fields\Panel');
});

test('Panel 1 contains correct fields', function () {
    $model = new ModelWithGroups;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];

    expect($panel1['fields'])->toHaveCount(3);
});

test('Tab 2 contains Panel 3 with one field', function () {
    $model = new ModelWithGroups;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];
    $panel3 = $tab2['fields'][0];

    expect($panel3['fields'])->toHaveCount(1);
});
