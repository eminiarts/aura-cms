<?php

use Aura\Base\Resource;

class ModelRecursive extends Resource
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
                'fields' => [],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 2',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'fields' => [],
                'exclude_level' => 1,
            ],
            [
                'name' => 'Repeater 3',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'fields' => [],
                'exclude_level' => 1,
            ],
            [
                'name' => 'Field in Repeater 3',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Field2 in Repeater 3',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'fields' => [],
                'exclude_level' => 2,
            ],
            [
                'name' => 'Repeater 4',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'fields' => [],
            ],
            [
                'name' => 'Field in Tab 2',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Tab 3',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'fields' => [],
            ],
            [
                'name' => 'Field in Tab 3',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('root structure has single Tabs wrapper', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('Tab 1 contains two panels', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['name'])->toBe('Tab 1')
        ->and($tab1['fields'])->toHaveCount(2);
});

test('Panel 1 contains three repeaters', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    $panel1 = $fields[0]['fields'][0]['fields'][0];

    expect($panel1['name'])->toBe('Panel 1')
        ->and($panel1['fields'][0]['name'])->toBe('Repeater 1')
        ->and($panel1['fields'][1]['name'])->toBe('Repeater 2')
        ->and($panel1['fields'][2]['name'])->toBe('Repeater 3')
        ->and($panel1['fields'])->toHaveCount(3);
});

test('Repeater 1 has no nested fields', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    $repeater1 = $fields[0]['fields'][0]['fields'][0]['fields'][0];

    expect($repeater1['fields'])->toHaveCount(0);
});

test('Tab 2 contains text field', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];

    expect($tab2['name'])->toBe('Tab 2')
        ->and($tab2['fields'][0]['name'])->toBe('Field in Tab 2');
});

test('Tab 3 contains text field', function () {
    $model = new ModelRecursive;
    $fields = $model->getGroupedFields();

    $tab3 = $fields[0]['fields'][2];

    expect($tab3['name'])->toBe('Tab 3')
        ->and($tab3['fields'][0]['name'])->toBe('Field in Tab 3');
});
