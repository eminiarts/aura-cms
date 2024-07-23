<?php

use Aura\Base\Resource;

class ModelWithGroups extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'global' => true,
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'style' => [],
            ],
            [
                'label' => 'Text 1.1',
                'name' => 'Text 1.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'label' => 'Repeater 1.2',
                'name' => 'Repeater 1.2',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-1-2',
                'style' => [],
            ],
            [
                'label' => 'Text 1.2.1',
                'name' => 'Text 1.2.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-2-1',
            ],
            [
                'label' => 'Repeater 1.2.2',
                'name' => 'Repeater 1.2.2',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-1-2-2',
                'style' => [],
            ],
            [
                'label' => 'Total 1.2.2.1',
                'name' => 'Total 1.2.2.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-1-2-2-1',
            ],
            [
                'label' => 'Panel 2',
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
            ],
            [
                'label' => 'Repeater 2.1',
                'name' => 'Repeater 2.1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-2-1',
                'style' => [],
            ],
            [
                'label' => 'Repeater 2.1.1',
                'name' => 'Repeater 2.1.1',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'repeater-2-1-1',
                'style' => [],
            ],
            [
                'label' => 'Total 2.1.1.1',
                'name' => 'Total 2.1.1.1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total-2-1-1-1',
            ],

            [
                'label' => 'Tab 2',
                'global' => true,
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Panel 3',
                'name' => 'Panel 3',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-3',
                'style' => [],
            ],
            [
                'label' => 'Text 3',
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text3',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('fields get grouped when field group is true', function () {
    $model = new ModelWithGroups;

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertCount(2, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['slug'], 'tab-1');
    $this->assertCount(2, $fields[0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Panel 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['type'], 'Aura\\Base\\Fields\\Panel');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['type'], 'Aura\\Base\\Fields\\Panel');
    $this->assertCount(3, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertCount(1, $fields[0]['fields'][1]['fields'][0]['fields']);
});
