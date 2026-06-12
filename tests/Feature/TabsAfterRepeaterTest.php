<?php

use Aura\Base\Resource;

class TabsAfterRepeaterModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1',
            ],
            [
                'name' => 'Repeater 1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],

            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('tabs container wraps all fields correctly when repeater is present', function () {
    $model = new TabsAfterRepeaterModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs')
        ->and($fields[0]['fields'])->toHaveCount(2);
});

test('repeater stays inside first tab not treated as tab boundary', function () {
    $model = new TabsAfterRepeaterModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['name'])->toBe('Tab 1')
        ->and($tab1['fields'])->toHaveCount(1);

    // The repeater should be the first (and only) field in tab 1
    $repeater = $tab1['fields'][0];
    expect($repeater['type'])->toBe('Aura\Base\Fields\Repeater')
        ->and($repeater['slug'])->toBe('repeater-1');
});

test('second tab follows after repeater correctly', function () {
    $model = new TabsAfterRepeaterModel;
    $fields = $model->getGroupedFields();

    $tab2 = $fields[0]['fields'][1];

    expect($tab2['name'])->toBe('Tab 2')
        ->and($tab2['slug'])->toBe('tab2')
        ->and($tab2['fields'])->toHaveCount(1)
        ->and($tab2['fields'][0]['slug'])->toBe('text2');
});

test('repeater field has text nested inside it', function () {
    $model = new TabsAfterRepeaterModel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];
    $repeater = $tab1['fields'][0];

    expect($repeater['fields'])->toHaveCount(1)
        ->and($repeater['fields'][0]['slug'])->toBe('text1')
        ->and($repeater['fields'][0]['type'])->toBe('Aura\Base\Fields\Text');
});
