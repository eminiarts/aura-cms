<?php

use Aura\Base\Resource;

class MultipleTabsInPanelInTabsTestModelWithAnotherPanel extends Resource
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
                // 'wrap' => true,
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
                // 'nested' => false,
                // 'exclude_from_nesting' => true,
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
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-2-1',
            ],
        ];
    }
}

test('multiple tabs in panels in tabs are possible', function () {
    $model = new MultipleTabsInPanelInTabsTestModelWithAnotherPanel;

    ray()->clearScreen();

    $fields = $model->getGroupedFields();

    ray($fields)->red();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Aura\Base\Fields\Tabs');
    $this->assertCount(2, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Panel 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['name'], 'Panel 2');
    $this->assertCount(1, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertCount(2, $fields[0]['fields'][0]['fields'][0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Aura\Base\Fields\Tabs');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Tab 1 in Panel');
});
