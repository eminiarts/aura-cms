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
                // 'exclude_level' => 3,
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel2',
                'exclude_level' => 3,
                'same_level_grouping' => false,
                // 'exclude_level' => 2,
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

test('multiple tabs in panels in tabs are possible', function () {
    $model = new MultipleTabsBelowEachOtherModel;

    $fields = $model->getGroupedFields();

    // Check root level structure
    expect($fields)->toHaveCount(1);
    expect($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
    expect($fields[0]['fields'])->toHaveCount(1);

    // Check Tab 1
    expect($fields[0]['fields'][0]['name'])->toBe('Tab 1');
    expect($fields[0]['fields'][0]['_id'])->toBe(2);
    expect($fields[0]['fields'][0]['_parent_id'])->toBe(1);

    // Check Panel 1
    $panel1 = $fields[0]['fields'][0]['fields'][0];
    expect($panel1['name'])->toBe('Panel 1');
    expect($panel1['_id'])->toBe(3);
    expect($panel1['_parent_id'])->toBe(2);

    // Check Panel 2
    $panel2 = $panel1['fields'][1];
    expect($panel2['name'])->toBe('Panel 2');
    expect($panel2['type'])->toBe('Aura\Base\Fields\Panel');
    expect($panel2['_id'])->toBe(9);
    expect($panel2['_parent_id'])->toBe(3); // Should be child of Panel 1
    expect($panel2['exclude_level'])->toBe(3);

    // Check Panel 2's tabs structure
    expect($panel2['fields'][0]['name'])->toBe('Aura\Base\Fields\Tabs');
    expect($panel2['fields'][0]['fields'])->toHaveCount(2);
    expect($panel2['fields'][0]['fields'][0]['name'])->toBe('Tab 1 in Panel2');
    expect($panel2['fields'][0]['fields'][1]['name'])->toBe('Tab 2 in Panel2');
});
