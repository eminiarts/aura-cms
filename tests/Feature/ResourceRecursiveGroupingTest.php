<?php

use Eminiarts\Aura\Resource;

class ModelRecursive extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'fields' => [],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Field in Repeater 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
            ],
            [
                'name' => 'Field2 in Repeater 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'fields' => [],
            ],
            [
                'name' => 'Repeater 4',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'fields' => [],
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'fields' => [],
            ],
            [
                'name' => 'Field in Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
            ],
            [
                'name' => 'Tab 3',
                'global' => true,
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'fields' => [],
            ],
            [
                'name' => 'Field in Tab 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('recursive field function', function () {
    $model = new ModelRecursive();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertCount(2, $fields[0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['name'], 'Tabs');
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Panel 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Repeater 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][1]['name'], 'Repeater 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][2]['name'], 'Repeater 3');
    $this->assertCount(3, $fields[0]['fields'][0]['fields'][0]['fields']);
    $this->assertCount(0, $fields[0]['fields'][0]['fields'][0]['fields'][0]['fields']);
    $this->assertEquals($fields[0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals('Field in Tab 2', $fields[0]['fields'][1]['fields'][0]['name']);
    $this->assertEquals('Tab 3', $fields[0]['fields'][2]['name']);
    $this->assertEquals('Field in Tab 3', $fields[0]['fields'][2]['fields'][0]['name']);
});
