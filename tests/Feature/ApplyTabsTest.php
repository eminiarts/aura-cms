<?php

use Aura\Base\Resource;

class ApplyTabsTestModel extends Resource
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

test('fields get grouped when field group is true', function () {
    $model = new ApplyTabsTestModel;

    $tabs = $model->getGroupedFields();

    $this->assertCount(1, $tabs);
   
    expect($tabs[0])->toHaveKeys([
        'label', 'name', 'type', 'slug', 'field', '_id', '_parent_id', 
        'conditional_logic', 'fields'
    ]);

    // Check wrapper field
    expect($tabs[0]['label'])->toBe('Aura\Base\Fields\Tabs');
    expect($tabs[0]['type'])->toBe('Aura\Base\Fields\Tabs');
    expect($tabs[0]['slug'])->toBe('aurabasefieldstabs');
    expect($tabs[0]['_id'])->toBe(1);
    expect($tabs[0]['_parent_id'])->toBeNull();

    // Check tabs array
    expect($tabs[0]['fields'])->toHaveCount(2);

    // Check first tab
    expect($tabs[0]['fields'][0]['label'])->toBe('Tab 1');
    expect($tabs[0]['fields'][0]['type'])->toBe('Aura\Base\Fields\Tab');
    expect($tabs[0]['fields'][0]['field_type'])->toBe('tab');
    expect($tabs[0]['fields'][0]['_parent_id'])->toBe(1);
    expect($tabs[0]['fields'][0]['fields'])->toHaveCount(1);

    // Check second tab
    expect($tabs[0]['fields'][1]['label'])->toBe('Tab 2');
    expect($tabs[0]['fields'][1]['type'])->toBe('Aura\Base\Fields\Tab');
    expect($tabs[0]['fields'][1]['field_type'])->toBe('tab');
    expect($tabs[0]['fields'][1]['_parent_id'])->toBe(1);
    expect($tabs[0]['fields'][1]['fields'])->toHaveCount(1);
});
