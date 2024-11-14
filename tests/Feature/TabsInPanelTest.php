<?php

use Aura\Base\Resource;

class TabsInPanelTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [

            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
            ],
            [
                'label' => 'Tab 1 in Panel',
                'name' => 'Tab 1 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-1',
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'label' => 'Tab 2 in Panel',
                'name' => 'Tab 2 in Panel',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab1-2',
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('fields get grouped when field group is true', function () {
    $model = new TabsInPanelTestModel;

    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1);

    // Check panel wrapper
    expect($fields[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id',
        'conditional_logic', 'fields',
    ]);
    expect($fields[0]['label'])->toBe('Panel 1');
    expect($fields[0]['type'])->toBe('Aura\\Base\\Fields\\Panel');
    expect($fields[0]['slug'])->toBe('panel');
    expect($fields[0]['field_type'])->toBe('panel');
    expect($fields[0]['_id'])->toBe(1);
    expect($fields[0]['_parent_id'])->toBeNull();

    // Check tabs wrapper inside panel
    expect($fields[0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['label'])->toBe('Aura\\Base\\Fields\\Tabs');
    expect($fields[0]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Tabs');
    expect($fields[0]['fields'][0]['slug'])->toBe('aurabasefieldstabs');
    expect($fields[0]['fields'][0]['_id'])->toBe(2);
    expect($fields[0]['fields'][0]['_parent_id'])->toBe(1);

    // Check tabs array
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(2);

    // Check first tab
    expect($fields[0]['fields'][0]['fields'][0]['label'])->toBe('Tab 1 in Panel');
    expect($fields[0]['fields'][0]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Tab');
    expect($fields[0]['fields'][0]['fields'][0]['field_type'])->toBe('tab');
    expect($fields[0]['fields'][0]['fields'][0]['_id'])->toBe(3);
    expect($fields[0]['fields'][0]['fields'][0]['_parent_id'])->toBe(2);
    expect($fields[0]['fields'][0]['fields'][0]['fields'])->toHaveCount(1);

    // Check text field inside first tab
    expect($fields[0]['fields'][0]['fields'][0]['fields'][0]['label'])->toBe('Text 1');
    expect($fields[0]['fields'][0]['fields'][0]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Text');
    expect($fields[0]['fields'][0]['fields'][0]['fields'][0]['field_type'])->toBe('input');
    expect($fields[0]['fields'][0]['fields'][0]['fields'][0]['_id'])->toBe(4);
    expect($fields[0]['fields'][0]['fields'][0]['fields'][0]['_parent_id'])->toBe(3);

    // Check second tab
    expect($fields[0]['fields'][0]['fields'][1]['label'])->toBe('Tab 2 in Panel');
    expect($fields[0]['fields'][0]['fields'][1]['type'])->toBe('Aura\\Base\\Fields\\Tab');
    expect($fields[0]['fields'][0]['fields'][1]['field_type'])->toBe('tab');
    expect($fields[0]['fields'][0]['fields'][1]['_id'])->toBe(5);
    expect($fields[0]['fields'][0]['fields'][1]['_parent_id'])->toBe(2);
    expect($fields[0]['fields'][0]['fields'][1]['fields'])->toHaveCount(1);

    // Check text field inside second tab
    expect($fields[0]['fields'][0]['fields'][1]['fields'][0]['label'])->toBe('Text 2');
    expect($fields[0]['fields'][0]['fields'][1]['fields'][0]['type'])->toBe('Aura\\Base\\Fields\\Text');
    expect($fields[0]['fields'][0]['fields'][1]['fields'][0]['field_type'])->toBe('input');
    expect($fields[0]['fields'][0]['fields'][1]['fields'][0]['_id'])->toBe(6);
    expect($fields[0]['fields'][0]['fields'][1]['fields'][0]['_parent_id'])->toBe(5);
});
