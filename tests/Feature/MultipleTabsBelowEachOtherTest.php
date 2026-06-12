<?php

use Aura\Base\Resource;

class MultipleTabsBelowEachOtherModel extends Resource
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
                'wrap' => true,
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
                'exclude_level' => 3,
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
        ];
    }
}

test('root structure has single Tabs wrapper', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($fields[0]['fields'])->toHaveCount(1);
});

test('Tab 1 is properly nested at root level', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['name'])->toBe('Tab 1')
        ->and($tab1['_id'])->toBe(2)
        ->and($tab1['_parent_id'])->toBe(1);
});

test('Panel 1 is nested inside Tab 1', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];

    expect($panel1['name'])->toBe('Panel 1')
        ->and($panel1['_id'])->toBe(3)
        ->and($panel1['_parent_id'])->toBe(2);
});

test('Panel 2 is nested inside Panel 1', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];
    $panel2 = $panel1['fields'][1];

    expect($panel2['name'])->toBe('Panel 2')
        ->and($panel2['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($panel2['_id'])->toBe(9)
        ->and($panel2['_parent_id'])->toBe(3)
        ->and($panel2['exclude_level'])->toBe(3);
});

test('Panel 2 contains Tabs wrapper with two tabs', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];
    $panel2 = $panel1['fields'][1];

    expect($panel2['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($panel2['fields'][0]['fields'])->toHaveCount(2)
        ->and($panel2['fields'][0]['fields'][0]['name'])->toBe('Tab 1 in Panel2')
        ->and($panel2['fields'][0]['fields'][1]['name'])->toBe('Tab 2 in Panel2');
});

test('nested tabs in Panel 2 contain correct text fields', function () {
    $model = new MultipleTabsBelowEachOtherModel;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];
    $panel2 = $panel1['fields'][1];
    $tabs = $panel2['fields'][0];

    expect($tabs['fields'][0]['fields'][0]['slug'])->toBe('text3')
        ->and($tabs['fields'][1]['fields'][0]['slug'])->toBe('text4');
});
