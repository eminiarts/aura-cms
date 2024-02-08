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
    $model = new ApplyTabsTestModel();

    $tabs = $model->getGroupedFields();

    $this->assertCount(1, $tabs);
    $this->assertEquals($tabs[0]['name'], 'Tabs');
    $this->assertCount(2, $tabs[0]['fields']);
    $this->assertEquals($tabs[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($tabs[0]['fields'][0]['type'], 'Aura\\Base\\Fields\\Tab');
    $this->assertCount(1, $tabs[0]['fields'][0]['fields']);
    $this->assertCount(1, $tabs[0]['fields'][1]['fields']);
});
