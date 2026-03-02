<?php

use Aura\Base\Resource;

class ResourceTabsModel1 extends Resource
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
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'other',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Total 2',
                'name' => 'Total 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-2',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

class ResourceTabsModel2 extends Resource
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
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'other',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Total 2',
                'name' => 'Total 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-2',
            ],
            [
                'label' => 'Tab 3',
                'name' => 'Tab 3',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-3',
                'style' => [],
            ],
            [
                'label' => 'Total 3',
                'name' => 'Total 3',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-3',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('resource groups fields into single Tabs wrapper', function () {
    $model = new ResourceTabsModel1;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe('Aura\Base\Fields\Tabs');
});

test('first tab contains correct fields', function () {
    $model = new ResourceTabsModel1;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['name'])->toBe('Tab 1')
        ->and($tab1['slug'])->toBe('tab-1')
        ->and($tab1['fields'])->toHaveCount(2)
        ->and($tab1['fields'][0]['label'])->toBe('Total')
        ->and($tab1['fields'][0]['slug'])->toBe('total')
        ->and($tab1['fields'][1]['slug'])->toBe('other');
});

test('second tab contains correct fields', function () {
    $model = new ResourceTabsModel1;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];

    expect($tab2['name'])->toBe('Tab 2')
        ->and($tab2['slug'])->toBe('tab-2')
        ->and($tab2['fields'])->toHaveCount(1)
        ->and($tab2['fields'][0]['slug'])->toBe('total-2');
});

test('extended model with three tabs groups correctly', function () {
    $model = new ResourceTabsModel2;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe('Aura\Base\Fields\Tabs')
        ->and($fields[0]['fields'])->toHaveCount(3);
});

test('extended model tabs have correct names', function () {
    $model = new ResourceTabsModel2;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'][0]['name'])->toBe('Tab 1')
        ->and($fields[0]['fields'][0]['slug'])->toBe('tab-1')
        ->and($fields[0]['fields'][1]['name'])->toBe('Tab 2')
        ->and($fields[0]['fields'][2]['slug'])->toBe('tab-3');
});

test('extended model tab fields are grouped correctly', function () {
    $model = new ResourceTabsModel2;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'][0]['fields'][0]['label'])->toBe('Total')
        ->and($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('total')
        ->and($fields[0]['fields'][0]['fields'][1]['slug'])->toBe('other')
        ->and($fields[0]['fields'][1]['fields'][0]['slug'])->toBe('total-2')
        ->and($fields[0]['fields'][2]['fields'][0]['slug'])->toBe('total-3');
});
