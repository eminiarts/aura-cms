<?php

use Eminiarts\Aura\Models\Post;

class PanelInTabsModel extends Post
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
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'App\\Aura\\Fields\\Panel',
                'slug' => 'panel',
                'style' => [],
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Text 2',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-2-1',
            ],
        ];
    }
}

test('fields get grouped when field group is true', function () {
    $model = new PanelInTabsModel();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Tabs');
    $this->assertCount(2, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Panel 1');
    $this->assertCount(1, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['type'], 'App\\Aura\\Fields\\Tab');
    $this->assertCount(1, $fields[0]['fields'][0]['fields']);
    $this->assertCount(1, $fields[0]['fields'][1]['fields']);
});
