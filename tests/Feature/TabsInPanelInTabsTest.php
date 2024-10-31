<?php

use Aura\Base\Resource;

class TabsInPanelInTabsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
            ],
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
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
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
    $model = new TabsInPanelInTabsTestModel;

    $fields = $model->getGroupedFields();

    ray($fields);

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Tabs');
    $this->assertCount(2, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Panel 1');
    $this->assertCount(1, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertCount(2, $fields[0]['fields'][0]['fields'][0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Tabs');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Tab 1 in Panel');
});
