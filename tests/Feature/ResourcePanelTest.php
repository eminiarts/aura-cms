<?php

use Aura\Base\Resource;

class ModelWithPanel extends Resource
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
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
            [
                'label' => 'Panel 2',
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'other',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('model get tabs with panels', function () {
    $model = new ModelWithPanel();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Aura\Base\Fields\Tabs');
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['slug'], 'tab-1');
    $this->assertCount(2, $fields[0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['type'], 'Aura\\Base\\Fields\\Panel');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['type'], 'Aura\\Base\\Fields\\Panel');
    $this->assertCount(1, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertCount(1, $fields[0]['fields'][0]['fields'][1]['fields']);
});
