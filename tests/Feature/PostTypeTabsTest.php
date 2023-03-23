<?php

use Eminiarts\Aura\Resource;

class Model1 extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total',
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'other',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Total 2',
                'name' => 'Total 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total-2',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('model get tabs', function () {
    $model = new Model1();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['slug'], 'tab-1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['label'], 'Total');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['slug'], 'total');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['slug'], 'other');
    $this->assertEquals($fields[0]['fields'][1]['fields'][0]['slug'], 'total-2');
});

class Model2 extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total',
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'other',
            ],
            [
                'label' => 'Tab 2',
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'label' => 'Total 2',
                'name' => 'Total 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total-2',
            ],
            [
                'label' => 'Tab 3',
                'name' => 'Tab 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-3',
                'style' => [],
            ],
            [
                'label' => 'Total 3',
                'name' => 'Total 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total-3',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('model get tabs extended', function () {
    $model = new Model2();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['slug'], 'tab-1');
    $this->assertEquals($fields[0]['fields'][2]['slug'], 'tab-3');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['label'], 'Total');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['slug'], 'total');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['slug'], 'other');
    $this->assertEquals($fields[0]['fields'][1]['fields'][0]['slug'], 'total-2');
});
